<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel\Listeners;

use Illuminate\Cache\CacheManager;
use Spiral\RoadRunnerLaravel\Events\Contracts\WithApplication;
use Illuminate\Contracts\Config\Repository as ConfigRepository;

/**
 * @link https://github.com/laravel/octane/blob/1.x/src/Listeners/FlushArrayCache.php
 */
class FlushArrayCacheListener implements ListenerInterface
{
    /**
     * {@inheritdoc}
     */
    public function handle($event): void
    {
        if ($event instanceof WithApplication) {
            $app = $event->application();

            /** @var ConfigRepository $config */
            $config = $app->make(ConfigRepository::class);

            /** @var CacheManager $cache_manager */
            $cache_manager = $app->make('cache');

            foreach ($config->get('cache.stores') as $name => $options) {
                if (($options['driver'] ?? '') === 'array') {
                    $cache_manager->store($name)->getStore()->flush();
                }
            }
        }
    }
}
