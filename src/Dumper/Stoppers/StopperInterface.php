<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel\Dumper\Stoppers;

/**
 * @internal
 */
interface StopperInterface
{
    /**
     * Stops the execution.
     *
     * @return void
     */
    public function stop(): void;
}
