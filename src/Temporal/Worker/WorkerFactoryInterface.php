<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel\Temporal\Worker;

use Temporal\Worker\WorkerInterface;

interface WorkerFactoryInterface
{
    /**
     * Creates a new Temporal worker.
     *
     * @param non-empty-string $name
     */
    public function create(string $name): WorkerInterface;
}
