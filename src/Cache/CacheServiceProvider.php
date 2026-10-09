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
    #[\Override]
    public function register(): void
    {
        $this->app->booting(static function (): void {
            Cache::extend('roadrunner', function () {
                $env = Environment::fromGlobals();

                /** @var non-empty-string $rpcAddress */
                $rpcAddress = $env->getRPCAddress();
                $factory = new Factory(RPC::create($rpcAddress));

                $storage = config('roadrunner.cache.storage', 'cache');
                \is_string($storage) && $storage !== '' or throw new \InvalidArgumentException(
                    'The `roadrunner.cache.storage` option must be a non-empty string.',
                );

                return Cache::repository(
                    new RoadRunnerStore($factory->select($storage)),
                );
            });
        });
    }
}
