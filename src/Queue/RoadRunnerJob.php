<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel\Queue;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Queue\Job as JobContract;
use Illuminate\Queue\Jobs\Job;
use Spiral\RoadRunner\Jobs\Task\ReceivedTaskInterface;

class RoadRunnerJob extends Job implements JobContract
{
    private readonly array $payload;

    public function __construct(
        Application $container,
        private readonly ReceivedTaskInterface $task,
    ) {
        $this->container = $container;
        $this->payload = \json_decode($this->task->getPayload(), true);
    }

    public function getJobId(): string
    {
        return $this->task->getId();
    }

    public function getRawBody(): string
    {
        return $this->payload();
    }

    public function payload(): array
    {
        return $this->payload ?? [];
    }

    public function attempts(): int
    {
        return (int) $this->task->getHeaderLine('attempts');
    }

    public function fire(): void
    {
        parent::fire();

        $this->task->complete();
    }

    protected function failed($e): void
    {
        $attempts = $this->attempts();

        $this->task
            ->withHeader('attempts', (string) ++$attempts)
            ->fail($e->getMessage());

        parent::failed($e);
    }
}
