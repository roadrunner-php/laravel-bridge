<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel\Listeners;

use Illuminate\Database\Connection;
use Illuminate\Database\DatabaseManager;
use Spiral\RoadRunnerLaravel\Events\Contracts\WithApplication;

/**
 * @link https://github.com/laravel/octane/blob/1.x/src/Listeners/FlushDatabaseQueryLog.php
 */
class FlushDatabaseQueryLogListener implements ListenerInterface
{
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

            /** @var DatabaseManager $database_manager */
            $database_manager = $app->make($database_abstract);

            foreach ($database_manager->getConnections() as $connection) {
                if ($connection instanceof Connection) {
                    $connection->flushQueryLog();
                }
            }
        }
    }
}
