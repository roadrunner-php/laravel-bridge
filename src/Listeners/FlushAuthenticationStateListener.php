<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel\Listeners;

use Spiral\RoadRunnerLaravel\Events\Contracts\WithApplication;

/**
 * @link https://github.com/laravel/octane/blob/1.x/src/Listeners/FlushAuthenticationState.php
 */
class FlushAuthenticationStateListener implements ListenerInterface
{
    use Traits\InvokerTrait;

    /**
     * {@inheritdoc}
     */
    public function handle($event): void
    {
        if ($event instanceof WithApplication) {
            $app = $event->application();

            if ($app instanceof \Illuminate\Container\Container) {
                if ($app->resolved($auth_driver_abstract = 'auth.driver')) {
                    $app->forgetInstance($auth_driver_abstract);
                }
            }

            if (! $app->resolved($auth_abstract = 'auth')) {
                return;
            }

            /** @var \Illuminate\Auth\AuthManager $auth */
            $auth = $app->make($auth_abstract);

            /**
             * Method `setApplication` for the Auth Manager available since Laravel v8.35.0.
             *
             * @link https://git.io/JsgTx Source code (v8.35.0)
             * @see  \Illuminate\Auth\AuthManager::setApplication()
             */
            if (! $this->invokeMethod($auth, 'setApplication', $app)) {
                $this->setProperty($auth, 'app', $app);
            }

            /**
             * Method `forgetGuards` for the Auth Manager available since Laravel v8.35.0.
             *
             * @link https://git.io/Jsgkt Source code (v8.35.0)
             * @see  \Illuminate\Auth\AuthManager::forgetGuards()
             */
            if (! $this->invokeMethod($auth, 'forgetGuards')) {
                $this->setProperty($auth, 'guards', []);
            }
        }
    }
}
