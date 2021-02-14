<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel;

interface WorkerInterface
{
    /**
     * Start worker loop.
     *
     * @param RunParams $params
     *
     * @return void
     */
    public function start(RunParams $params): void;
}
