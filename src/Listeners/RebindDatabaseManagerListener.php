<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel\Listeners;

use Spiral\RoadRunnerLaravel\Events\Contracts\WithApplication;

/**
 * @link https://github.com/laravel/octane/blob/1.x/src/Listeners/GiveNewApplicationInstanceToDatabaseManager.php
 */
class RebindDatabaseManagerListener implements ListenerInterface
{
    use Traits\InvokerTrait;

    /**
     * {@inheritdoc}
     */
    public function handle($event): void
    {
        if ($event instanceof WithApplication) {
            $app = $event->application();

            if (!$app->resolved($database_abstract = 'db')) {
                return;
            }

            /** @var \Illuminate\Database\DatabaseManager $database_manager */
            $database_manager = $app->make($database_abstract);

            /**
             * Method `setApplication` for the DatabaseManager available since Laravel v8.39.0.
             *
             * @link https://git.io/JszsX Source code (v8.39.0)
             * @see  \Illuminate\Database\DatabaseManager::setApplication
             */
            if (! $this->invokeMethod($database_manager, 'setApplication', $app)) {
                $this->setProperty($database_manager, 'app', $app);
            }
        }
    }
}
