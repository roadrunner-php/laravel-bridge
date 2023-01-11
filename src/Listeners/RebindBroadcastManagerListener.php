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

            if (! $app->resolved($broadcast_manager_abstract = BroadcastManager::class)) {
                return;
            }

            /** @var BroadcastManager $broadcast_manager */
            $broadcast_manager = $app->make($broadcast_manager_abstract);

            $broadcast_manager->setApplication($app);

            // Forgetting drivers will flush all channel routes which is unwanted...
            // $broadcast_manager->forgetDrivers();
        }
    }
}
