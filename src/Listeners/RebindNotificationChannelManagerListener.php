<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel\Listeners;

use Illuminate\Notifications\ChannelManager;
use Spiral\RoadRunnerLaravel\Events\Contracts\WithApplication;

/**
 * @link https://github.com/laravel/octane/blob/1.x/src/Listeners/GiveNewApplicationInstanceToNotificationChannelManager.php
 */
class RebindNotificationChannelManagerListener implements ListenerInterface
{
    use Traits\InvokerTrait;

    /**
     * {@inheritdoc}
     */
    public function handle($event): void
    {
        if ($event instanceof WithApplication) {
            $app = $event->application();

            if (!$app->resolved($channel_manager_abstract = ChannelManager::class)) {
                return;
            }

            /** @var ChannelManager $channel_manager */
            $channel_manager = $app->make($channel_manager_abstract);

            $channel_manager->setContainer($app);
            $channel_manager->forgetDrivers();
        }
    }
}
