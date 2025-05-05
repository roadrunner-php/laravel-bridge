<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel\Cache;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\ServiceProvider;
use Spiral\Goridge\RPC\RPC;
use Spiral\RoadRunner\Environment;
use Spiral\RoadRunner\KeyValue\Factory;

final class CacheServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->booting(static function (): void {
            Cache::extend('roadrunner', function () {
                $env = Environment::fromGlobals();
                $factory = new Factory(RPC::create($env->getRPCAddress()));

                return Cache::repository(
                    new RoadRunnerStore(
                        $factory->select(config('roadrunner.cache.storage', 'cache')),
                    ),
                );
            });
        });
    }
}
