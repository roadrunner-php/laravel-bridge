<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel\Temporal\Worker;

use Temporal\Worker\WorkerInterface;
use Temporal\Worker\WorkerOptions;

interface WorkerFactoryWithOptionsInterface extends WorkerFactoryInterface
{
    /**
     * Creates a new Temporal worker with explicit options instead of the configured ones.
     *
     * @param non-empty-string $name
     * @param WorkerOptions|null $options Null means SDK defaults.
     */
    public function createWithOptions(string $name, ?WorkerOptions $options = null): WorkerInterface;
}
