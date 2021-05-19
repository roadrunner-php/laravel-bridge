<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel\Listeners;

use Illuminate\Contracts\Pipeline\Hub;
use Spiral\RoadRunnerLaravel\Events\Contracts\WithApplication;

/**
 * @link https://github.com/laravel/octane/blob/1.x/src/Listeners/GiveNewApplicationInstanceToQueueManager.php
 */
class RebindQueueManagerListener implements ListenerInterface
{
    use Traits\InvokerTrait;

    /**
     * {@inheritdoc}
     */
    public function handle($event): void
    {
        if ($event instanceof WithApplication) {
            $app = $event->application();

            if (!$app->resolved($queue_abstract = 'queue')) {
                return;
            }

            /** @var \Illuminate\Queue\QueueManager $queue_manager */
            $queue_manager = $app->make($queue_abstract);

            /**
             * Method `setContainer` for the QueueManager available since Laravel v8.35.0.
             *
             * @link https://git.io/JszXf Source code (v8.35.0)
             * @see  \Illuminate\Queue\QueueManager::setApplication
             */
            if (!$this->invokeMethod($queue_manager, 'setApplication', $app)) {
                $this->setProperty($queue_manager, 'app', $app);
            }
        }
    }
}
