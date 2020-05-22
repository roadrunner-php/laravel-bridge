<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel;

interface WorkerInterface
{
    /**
     * Start worker loop.
     *
     * @param bool|false $refresh_app
     *
     * @return void
     */
    public function start(bool $refresh_app = false): void;
}
