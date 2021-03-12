<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel\Tests;

use Illuminate\Support\Str;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Contracts\Console\Kernel;
use Spiral\RoadRunnerLaravel\ServiceProvider;

abstract class AbstractTestCase extends \Illuminate\Foundation\Testing\TestCase
{
    /**
     * @var string Path to the directory for temporary content.
     */
    private string $tmp_dir = __DIR__ . DIRECTORY_SEPARATOR . 'temp';

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

    /**
     * Create directory for temporary files. Created directory will be automatically removed on ::tearDown().
     *
     * @return string Absolute path to the directory
     */
    protected function createTemporaryDirectory(): string
    {
        $path = $this->tmp_dir . DIRECTORY_SEPARATOR . Str::lower(Str::random(6));

        (new Filesystem())->makeDirectory($path, 0755, true);

        return \realpath($path);
    }

    /**
     * {@inheritDoc}
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        $fs = new Filesystem();

        if ($fs->exists($path = $this->tmp_dir) && $fs->isDirectory($path)) {
            $fs->deleteDirectory($path, false);
        }
    }
}
