<?php

namespace Spiral\RoadRunnerLaravel\Events\Contracts;

use Illuminate\Contracts\Foundation\Application;

interface WithApplication
{
    /**
     * Get application instance.
     *
     * @return Application
     */
    public function application(): Application;
}
