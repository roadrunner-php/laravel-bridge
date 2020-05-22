<?php

declare(strict_types = 1);

namespace Spiral\RoadRunnerLaravel\Listeners;

use Illuminate\Container\Container;
use Spiral\RoadRunnerLaravel\ServiceProvider;
use Spiral\RoadRunnerLaravel\Events\Contracts\WithApplication;
use Illuminate\Contracts\Config\Repository as ConfigRepository;

/**
 * @link https://github.com/swooletw/laravel-swoole/blob/master/src/Server/Resetters/ClearInstances.php
 */
class ClearInstancesListener implements ListenerInterface
{
    /**
     * {@inheritdoc}
     */
    public function handle($event): void
    {
        if ($event instanceof WithApplication) {
            $app = $event->application();

            if ($app instanceof Container) {
                /** @var ConfigRepository $config */
                $config    = $app->make(ConfigRepository::class);
                $abstracts = (array) $config->get(ServiceProvider::getConfigRootKey() . '.clear_instances', []);

                foreach ($abstracts as $abstract) {
                    $app->forgetInstance($abstract);
                }
            }
        }
    }
}
