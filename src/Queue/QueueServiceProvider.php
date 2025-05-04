<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel\Queue;

use Illuminate\Support\ServiceProvider;

final class QueueServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->app['queue']->extend('roadrunner', static fn() => new RoadRunnerConnector());
    }
}
