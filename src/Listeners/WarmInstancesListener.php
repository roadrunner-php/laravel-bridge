<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel\Listeners;

use Spiral\RoadRunnerLaravel\ServiceProvider;
use Spiral\RoadRunnerLaravel\Events\Contracts\WithApplication;
use Illuminate\Contracts\Config\Repository as ConfigRepository;

class WarmInstancesListener implements ListenerInterface
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

            foreach ((array) $config->get(ServiceProvider::getConfigRootKey() . '.warm', []) as $abstract) {
                if (\is_string($abstract) && $app->bound($abstract)) {
                    $app->make($abstract);
                }
            }
        }
    }
}
