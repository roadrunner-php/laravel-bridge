<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel\Listeners;

/**
 * @link https://github.com/laravel/octane/blob/1.x/src/Listeners/FlushMonologState.php
 */
class FlushMonologStateListener implements ListenerInterface
{
    /**
     * {@inheritdoc}
     */
    public function handle($event): void
    {
        if ($event instanceof \Spiral\RoadRunnerLaravel\Events\Contracts\WithApplication) {
            $app = $event->application();

            if (!$app->resolved($log_abstract = 'log')) {
                return;
            }

            /** @var \Illuminate\Log\LogManager $log_manager */
            $log_manager = $app->make($log_abstract);

            foreach ($log_manager->getChannels() as $channel) {
                if ($channel instanceof \Illuminate\Log\Logger) {
                    $logger = $channel->getLogger();

                    if ($logger instanceof \Monolog\ResettableInterface) {
                        $logger->reset();
                    }
                }
            }
        }
    }
}
