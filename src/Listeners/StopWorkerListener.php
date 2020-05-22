<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel\Listeners;

use Spiral\RoadRunner\PSR7Client;
use Spiral\RoadRunnerLaravel\Events\Contracts\WithApplication;

/**
 * Common usage - stop worker on unhandled error occurring.
 *
 * @link https://roadrunner.dev/docs/php-restarting
 */
class StopWorkerListener implements ListenerInterface
{
    /**
     * {@inheritdoc}
     */
    public function handle($event): void
    {
        if ($event instanceof WithApplication) {
            /** @var PSR7Client $psr7_client */
            $psr7_client = $event->application()->make(PSR7Client::class);

            $psr7_client->getWorker()->stop();
        }
    }
}
