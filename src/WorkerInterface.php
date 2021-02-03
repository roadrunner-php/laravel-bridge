<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel;

interface WorkerInterface
{
    /**
     * Start worker loop.
     *
     * @param bool           $refresh_app
     * @param RunParams|null $params
     *
     * @return void
     */
    public function start(bool $refresh_app = false, ?RunParams $params = null): void;
}
