<?php

declare(strict_types = 1);

namespace Spiral\RoadRunnerLaravel\Listeners;

use Spiral\RoadRunnerLaravel\ServiceProvider;
use Spiral\RoadRunnerLaravel\Events\Contracts\WithApplication;
use Illuminate\Contracts\Config\Repository as ConfigRepository;

/**
 * @link https://github.com/swooletw/laravel-swoole/blob/master/src/Server/Resetters/ResetProviders.php
 */
class ResetProvidersListener implements ListenerInterface
{
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

                // It seems like not required.
                //
                //$closure = function () use ($app) {$this->{'app'} = $app;};
                //$reseter = $closure->bindTo($provider, $provider);
                //$reseter();

                if (\method_exists($provider, $register_method = 'register')) {
                    $provider->{$register_method}();
                }

                if (\method_exists($provider, $boot_method = 'boot')) {
                    $app->call([$provider, $boot_method]);
                }
            }
        }
    }
}
