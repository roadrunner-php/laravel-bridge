<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel;

interface WorkerInterface
{
    /**
     * Start worker loop.
     *
     * @throws \Throwable
     */
    public function start(WorkerOptionsInterface $options): void;
}
