<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel\Listeners;

use Spiral\RoadRunnerLaravel\ServiceProvider;
use Spiral\RoadRunnerLaravel\Events\Contracts\WithApplication;
use Illuminate\Contracts\Config\Repository as ConfigRepository;

/**
 * @link https://github.com/swooletw/laravel-swoole/blob/master/src/Server/Resetters/ResetProviders.php
 */
class ResetProvidersListener implements ListenerInterface
{
    use Traits\InvokerTrait;

    /**
     * {@inheritdoc}
     */
    public function handle($event): void
    {
        if ($event instanceof WithApplication) {
            $app = $event->application();

            /** @var ConfigRepository $config */
            $config    = $app->make(ConfigRepository::class);
            $providers = (array) $config->get(ServiceProvider::getConfigRootKey() . '.reset_providers', []);

            foreach (\array_unique($providers) as $provider_class) {
                /** @var \Illuminate\Support\ServiceProvider $provider */
                $provider = new $provider_class($app);

                $provider->register();

                if (\method_exists($provider, $boot_method = 'boot')) {
                    $app->call([$provider, $boot_method]);
                }
            }
        }
    }
}
