<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel\Temporal\Worker;

use Spiral\RoadRunnerLaravel\Temporal\Exception\WorkersRegistryException;
use Temporal\Worker\WorkerInterface;
use Temporal\Worker\WorkerOptions;

interface WorkersRegistryInterface
{
    /**
     * Register a new temporal worker with given task queue and options.
     *
     * @throws WorkersRegistryException
     */
    public function register(string $name, ?WorkerOptions $options): void;

    /**
     * Get or create worker by task queue name.
     */
    public function get(string $name): WorkerInterface;

    /**
     * Check if a worker with given task queue name registered.
     */
    public function has(string $name): bool;
}
