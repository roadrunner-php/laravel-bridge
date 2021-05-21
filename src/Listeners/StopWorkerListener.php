<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel\Listeners;

use Spiral\RoadRunner\Http\PSR7Worker;
use Spiral\RoadRunnerLaravel\Events\Contracts\WithApplication;

/**
 * Common usage - the worker stopping on an unhandled error occurring.
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
            /** @var PSR7Worker $psr7_worker */
            $psr7_worker = $event->application()->make(PSR7Worker::class);

            $psr7_worker->getWorker()->stop();
        }
    }
}
