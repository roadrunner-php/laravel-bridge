<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel\Queue;

use Illuminate\Contracts\Queue\Queue as QueueContract;
use Illuminate\Queue\Queue;
use Ramsey\Uuid\Uuid;
use RoadRunner\Jobs\DTO\V1\Stat;
use RoadRunner\Jobs\DTO\V1\Stats;
use Spiral\Goridge\RPC\RPCInterface;
use Spiral\RoadRunner\Jobs\Jobs;
use Spiral\RoadRunner\Jobs\KafkaOptions;
use Spiral\RoadRunner\Jobs\Options;
use Spiral\RoadRunner\Jobs\OptionsInterface;
use Spiral\RoadRunner\Jobs\Queue\Driver;
use Spiral\RoadRunner\Jobs\QueueInterface;
use Spiral\RoadRunnerLaravel\Queue\Contract\HasQueueOptions;

final class RoadRunnerQueue extends Queue implements QueueContract
{
    public function __construct(
        private readonly Jobs $jobs,
        private readonly RPCInterface $rpc,
        private readonly string $default = 'default',
        private readonly array $defaultOptions = [],
    ) {}

    public function push($job, $data = '', $queue = null): string
    {
        return $this->enqueueUsing(
            $job,
            $this->createPayload($job, $queue, $data),
            $queue,
            null,
            fn($payload, $queue) => $this->pushRaw($payload, $queue, $this->getJobOverrideOptions($job)),
        );
    }

    public function pushRaw($payload, $queue = null, array $options = []): string
    {
        $queue = $this->getQueue($queue, $options);

        $task = $queue->dispatch(
            $queue->create(self::resolveTaskName($payload), $payload),
        );

        return $task->getId();
    }

    public function later($delay, $job, $data = '', $queue = null): string
    {
        return $this->enqueueUsing(
            $job,
            $this->createPayload($job, $queue, $data),
            $queue,
            $delay,
            fn($payload, $queue) => $this->laterRaw($delay, $payload, $queue, $this->getJobOverrideOptions($job)),
        );
    }

    public function pop($queue = null): void
    {
        throw new \BadMethodCallException('Pop is not supported');
    }

    public function size($queue = null): int
    {
        $stats = $this->getStats($queue);

        return (int) $stats->getActive() + (int) $stats->getDelayed() + (int) $stats->getReserved();
    }

    public function pendingSize($queue = null): int
    {
        return (int) $this->getStats($queue)->getActive();
    }

    public function delayedSize($queue = null): int
    {
        return (int) $this->getStats($queue)->getDelayed();
    }

    public function reservedSize($queue = null): int
    {
        return (int) $this->getStats($queue)->getReserved();
    }

    public function creationTimeOfOldestPendingJob($queue = null): ?int
    {
        return null;
    }

    /**
     * Get the delay in seconds until the given DateTime.
     *
     * Despite the inherited name, this returns a *relative* offset (what
     * RoadRunner's `withDelay(int $seconds)` expects), not an absolute
     * UNIX timestamp like Laravel's own `InteractsWithTime::availableAt()`.
     * The previous implementation routed through `Carbon::diffInSeconds()`,
     * which since Carbon 3 (Laravel 12) returns a signed `float` — and
     * triggered a return-type TypeError on every `Queue::later(DateTime)`
     * call. Plain timestamp arithmetic is what Laravel's `secondsUntil()`
     * does and avoids the round-trip entirely.
     *
     * @param mixed $delay
     */
    protected function availableAt($delay = 0): int
    {
        $delay = $this->parseDateInterval($delay);

        return $delay instanceof \DateTimeInterface
            ? max(0, $delay->getTimestamp() - $this->currentTime())
            : (int) $delay;
    }

    /**
     * Resolve a task name from the queue payload Laravel hands us.
     *
     * Modern Laravel's `Queue::createPayload()` returns JSON-encoded bytes.
     * The previous code did bare offset access (`$payload['displayName']`)
     * on that string, which silently reads byte 0 ({), turning every task
     * name into `{` and emitting an "Illegal string offset" warning on
     * PHP 8.x. Decoding first is what the original code meant to do.
     */
    private static function resolveTaskName(string $payload): string
    {
        $decoded = json_decode($payload, true);

        if (
            is_array($decoded)
            && isset($decoded['displayName'])
            && is_string($decoded['displayName'])
            && $decoded['displayName'] !== ''
        ) {
            return $decoded['displayName'];
        }

        return Uuid::uuid4()->toString();
    }

    private function getQueue(?string $queue = null, array $options = []): QueueInterface
    {
        $queue = $this->jobs->connect($queue ?? $this->default, $this->getQueueOptions($options));

        if (!$this->getStats($queue->getName())->getReady()) {
            $queue->resume();
        }

        return $queue;
    }

    private function getQueueOptions(array $overrides = []): OptionsInterface
    {
        $config = array_merge($this->defaultOptions, $overrides);
        $options = new Options(
            $config['delay'] ?? OptionsInterface::DEFAULT_DELAY,
            $config['priority'] ?? OptionsInterface::DEFAULT_PRIORITY,
            $config['auto_ack'] ?? OptionsInterface::DEFAULT_AUTO_ACK,
        );

        return match ($config['driver'] ?? null) {
            Driver::Kafka => KafkaOptions::from($options)
                ->withTopic($config['topic'] ?? ($this->defaultOptions['topic'] ?? '')),
            default => $options,
        };
    }

    private function getStats(?string $queue = null): Stat
    {
        $queue ??= $this->default;

        $stats = $this->rpc->call('jobs.Stat', new Stats(), Stats::class)->getStats();

        /** @var Stat $stat */
        foreach ($stats as $stat) {
            if ($stat->getPipeline() === $queue) {
                return $stat;
            }
        }

        return new Stat();
    }

    private function getJobOverrideOptions(string|object $job): array
    {
        if (is_string($job) && class_exists($job)) {
            $job = app($job);
        }

        if ($job instanceof HasQueueOptions) {
            $options = $job->queueOptions();
            if ($options instanceof Options) {
                return $options->toArray();
            }
        }

        return [];
    }

    private function laterRaw(
        \DateTimeInterface|\DateInterval|int $delay,
        string $payload,
        ?string $queue = null,
        array $options = [],
    ): string {
        $queue = $this->getQueue($queue, $options);

        $task = $queue->dispatch(
            $queue
                ->create(self::resolveTaskName($payload), $payload)
                ->withDelay($this->availableAt($delay)),
        );

        return $task->getId();
    }
}
