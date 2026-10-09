<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel\Queue;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Queue\Job as JobContract;
use Illuminate\Queue\Jobs\Job;
use Spiral\RoadRunner\Jobs\Task\ReceivedTaskInterface;

class RoadRunnerJob extends Job implements JobContract
{
    /** @var array<mixed> */
    private readonly array $payload;

    public function __construct(
        Application $container,
        private readonly ReceivedTaskInterface $task,
    ) {
        $this->container = $container;
        $this->payload = \json_decode($this->task->getPayload(), true);
    }

    #[\Override]
    public function getJobId(): string
    {
        return $this->task->getId();
    }

    #[\Override]
    public function getRawBody(): string
    {
        return $this->task->getPayload();
    }

    /**
     * @return array<mixed>
     */
    #[\Override]
    public function payload(): array
    {
        return $this->payload ?? [];
    }

    #[\Override]
    public function attempts(): int
    {
        return (int) $this->task->getHeaderLine('attempts');
    }

    #[\Override]
    public function fire(): void
    {
        parent::fire();

        $this->task->complete();
    }

    #[\Override]
    public function release($delay = 0): void
    {
        $attempts = $this->attempts();

        $this->task
            ->withDelay($delay)
            ->withHeader('attempts', (string) ++$attempts)
            ->requeue('release');

        parent::release($delay);
    }

    #[\Override]
    protected function failed($e): void
    {
        $attempts = $this->attempts();

        $this->task
            ->withHeader('attempts', (string) ++$attempts)
            ->fail($e->getMessage());

        parent::failed($e);
    }
}
