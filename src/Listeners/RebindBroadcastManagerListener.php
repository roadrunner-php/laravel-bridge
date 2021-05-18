<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel\Listeners;

use Illuminate\Broadcasting\BroadcastManager;
use Spiral\RoadRunnerLaravel\Events\Contracts\WithApplication;

/**
 * @link https://github.com/laravel/octane/blob/1.x/src/Listeners/GiveNewApplicationInstanceToBroadcastManager.php
 */
class RebindBroadcastManagerListener implements ListenerInterface
{
    use Traits\InvokerTrait;

    /**
     * {@inheritdoc}
     */
    public function handle($event): void
    {
        if ($event instanceof WithApplication) {
            $app = $event->application();

            /** @var BroadcastManager $broadcast_manager */
            $broadcast_manager = $app->make(BroadcastManager::class);

            /**
             * Method `setApplication` for the BroadcastManager available since Laravel v8.35.0.
             *
             * @link https://git.io/Jszm3 Source code (v8.35.0)
             * @see  \Illuminate\Broadcasting\BroadcastManager::setApplication
             */
            if (! $this->invokeMethod($broadcast_manager, 'setApplication', $app)) {
                $this->setProperty($broadcast_manager, 'app', $app);
            }

            // Forgetting drivers will flush all channel routes which is unwanted...
            // $broadcast_manager->forgetDrivers();
        }
    }
}
