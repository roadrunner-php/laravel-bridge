<?php

declare(strict_types = 1);

namespace Spiral\RoadRunnerLaravel\Tests;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Testing\TestCase;
use Spiral\RoadRunnerLaravel\ServiceProvider;

abstract class AbstractTestCase extends TestCase
{
    /**
     * Creates the application.
     *
     * @return \Illuminate\Foundation\Application
     */
    public function createApplication()
    {
        /** @var \Illuminate\Foundation\Application $app */
        $app = require __DIR__ . '/../vendor/laravel/laravel/bootstrap/app.php';

        // $app->useStoragePath(...);
        // $app->loadEnvironmentFrom(...);

        $app->make(Kernel::class)->bootstrap();

        $app->register(ServiceProvider::class);

        return $app;
    }
}
