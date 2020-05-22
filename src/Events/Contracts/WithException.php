<?php

namespace Spiral\RoadRunnerLaravel\Events\Contracts;

use Throwable;

interface WithException
{
    /**
     * Get exception instance.
     *
     * @return Throwable
     */
    public function exception(): Throwable;
}
