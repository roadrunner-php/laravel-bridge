<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel\Listeners;

use Spiral\RoadRunnerLaravel\Events\Contracts\WithApplication;

/**
 * @link https://github.com/laravel/octane/blob/1.x/src/Listeners/FlushLogContext.php
 */
class FlushLogContextListener implements ListenerInterface
{
    use Traits\InvokerTrait;

    /**
     * {@inheritdoc}
     */
    public function handle($event): void
    {
        if ($event instanceof WithApplication) {
            $app = $event->application();

            if (!$app->resolved($log_abstract = 'log')) {
                return;
            }

            /** @var \Illuminate\Log\LogManager $log_manager */
            $log_manager = $app->make($log_abstract);

            if (($logger = $log_manager->driver()) instanceof \Illuminate\Log\Logger) {
                $logger->withoutContext();
            }
        }
    }
}
