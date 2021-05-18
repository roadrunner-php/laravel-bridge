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

            /**
             * Method `setContainer` for the ChannelManager available since Laravel v8.35.0.
             *
             * @link https://git.io/Jszo3 Source code (v8.35.0)
             * @see  ChannelManager::setContainer
             */
            if (! $this->invokeMethod($channel_manager, 'setContainer', $app)) {
                $this->setProperty($channel_manager, 'container', $app);
            }

            /**
             * Method `forgetDrivers` for the ChannelManager available since Laravel v8.35.0.
             *
             * @link https://git.io/JszK9 Source code (v8.35.0)
             * @see  ChannelManager::forgetDrivers
             */
            if (! $this->invokeMethod($channel_manager, 'forgetDrivers')) {
                $this->setProperty($channel_manager, 'drivers', []);
            }
        }
    }
}
