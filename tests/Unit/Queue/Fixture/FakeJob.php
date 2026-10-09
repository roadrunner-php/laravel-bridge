<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel\Tests\Unit\Queue\Fixture;

use Spiral\RoadRunnerLaravel\Queue\RoadRunnerJob;

/**
 * A {@see RoadRunnerJob} without a RoadRunner task behind it: it records what the worker does to it.
 */
final class FakeJob extends RoadRunnerJob
{
    public int $fired = 0;

    /** @var list<int> */
    public array $releases = [];

    /** @var list<\Throwable|null> */
    public array $failures = [];

    /**
     * @param array<string, mixed> $payload Extra payload keys, such as `maxTries`, `retryUntil` or `backoff`.
     * @param \Closure(self): void|null $onFire
     */
    public function __construct(
        private readonly array $payload = [],
        private readonly int $attempts = 0,
        private readonly ?\Closure $onFire = null,
    ) {}

    public function payload(): array
    {
        return $this->payload + ['job' => 'Illuminate\\Queue\\CallQueuedHandler@call', 'displayName' => 'App\\Jobs\\Demo'];
    }

    public function attempts(): int
    {
        return $this->attempts;
    }

    public function fire(): void
    {
        ++$this->fired;

        if ($this->onFire !== null) {
            ($this->onFire)($this);
        }
    }

    public function release($delay = 0): void
    {
        $this->releases[] = $delay;
        $this->released = true;
    }

    public function fail($e = null): void
    {
        $this->failures[] = $e;
        $this->markAsFailed();
        $this->delete();
    }
}
