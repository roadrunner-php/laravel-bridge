<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel\Queue;

use Illuminate\Queue\QueueManager;
use Illuminate\Support\ServiceProvider;

final class QueueServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->app->make(QueueManager::class)->extend('roadrunner', static fn() => new RoadRunnerConnector());
    }
}
