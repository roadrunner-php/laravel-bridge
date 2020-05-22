<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel\Listeners;

use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Connection as DatabaseConnection;
use Spiral\RoadRunnerLaravel\Events\Contracts\WithApplication;

class ResetDbConnectionsListener implements ListenerInterface
{
    /**
     * {@inheritdoc}
     */
    public function handle($event): void
    {
        if ($event instanceof WithApplication) {
            $manager = $event->application()->make('db');

            if ($manager instanceof DatabaseManager) {
                foreach ($manager->getConnections() as $connection) {
                    /** @var DatabaseConnection $connection */
                    if (\method_exists($connection, 'disconnect')) {
                        $connection->disconnect();
                    }
                }
            }
        }
    }
}
