<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel\Listeners;

use Spiral\RoadRunnerLaravel\Events\Contracts\WithApplication;

/**
 * @link https://github.com/laravel/octane/blob/1.x/src/Listeners/FlushDatabaseRecordModificationState.php
 */
class ResetDatabaseRecordModificationStateListener implements ListenerInterface
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

            foreach ($database_manager->getConnections() as $connection) {
                $connection->forgetRecordModificationState();
            }
        }
    }
}
