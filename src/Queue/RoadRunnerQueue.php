<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel\Queue;

use Illuminate\Contracts\Queue\Queue as QueueContract;
use Illuminate\Queue\Queue;
use Illuminate\Support\Carbon;
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
    ) {
    }

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
            $queue
                ->create($payload['displayName'] ?? Uuid::uuid4()->toString(), $payload),
        );

        return $task->getId();
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

    /**
     * Push a raw job onto the queue after a delay.
     */
    private function laterRaw(
        \DateTimeInterface|\DateInterval|int $delay,
        array $payload,
        ?string $queue = null,
        array $options = []
    ): string {
        $queue = $this->getQueue($queue, $options);

        $task = $queue->dispatch(
            $queue
                ->create($payload['displayName'] ?? Uuid::uuid4()->toString())
                ->withValue($payload)
                ->withDelay($this->availableAt($delay)),
        );

        return $task->getId();
    }

    /**
     * Get the "available at" UNIX timestamp.
     * @param mixed $delay
     */
    protected function availableAt($delay = 0): int
    {
        $delay = $this->parseDateInterval($delay);

        return $delay instanceof \DateTimeInterface
            ? Carbon::parse($delay)->diffInSeconds()
            : $delay;
    }

    public function pop($queue = null): void
    {
        throw new \BadMethodCallException('Pop is not supported');
    }

    public function size($queue = null): int
    {
        $stats = $this->getStats($queue);

        return $stats->getActive() + $stats->getDelayed();
    }
}
