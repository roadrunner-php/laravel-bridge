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
use Spiral\RoadRunner\Jobs\OptionsInterface;
use Spiral\RoadRunner\Jobs\QueueInterface;

final class RoadRunnerQueue extends Queue implements QueueContract
{
    public function __construct(
        private readonly Jobs $jobs,
        private readonly RPCInterface $rpc,
        private readonly OptionsInterface $options,
        private readonly string $default = 'default',
    ) {}

    public function push($job, $data = '', $queue = null): string
    {
        return $this->enqueueUsing(
            $job,
            $this->createPayload($job, $queue, $data),
            $queue,
            null,
            fn($payload, $queue) => $this->pushRaw($payload, $queue),
        );
    }

    public function pushRaw($payload, $queue = null, array $options = []): string
    {
        $queue = $this->getQueue($queue);

        $task = $queue->dispatch(
            $queue
                ->create($payload['displayName'] ?? Uuid::uuid4()->toString(), $payload),
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
            fn($payload, $queue) => $this->laterRaw($delay, $payload, $queue),
        );
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

    /**
     * Push a raw job onto the queue after a delay.
     */
    private function laterRaw(
        \DateTimeInterface|\DateInterval|int $delay,
        array $payload,
        ?string $queue = null,
    ): string {
        $queue = $this->getQueue($queue);

        $task = $queue->dispatch(
            $queue
                ->create($payload['displayName'] ?? Uuid::uuid4()->toString())
                ->withValue($payload)
                ->withDelay($this->availableAt($delay)),
        );

        return $task->getId();
    }

    private function getQueue(?string $queue = null): QueueInterface
    {
        $queue = $this->jobs->connect($queue ?? $this->default, $this->options);

        if (!$this->getStats($queue->getName())->getReady()) {
            $queue->resume();
        }

        return $queue;
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
}
