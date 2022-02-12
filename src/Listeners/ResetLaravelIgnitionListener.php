<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel\Listeners;

use Spiral\RoadRunnerLaravel\Events\Contracts\WithApplication;

/**
 * Target package: <https://github.com/spatie/laravel-ignition/>.
 */
class ResetLaravelIgnitionListener implements ListenerInterface
{
    use Traits\InvokerTrait;

    /**
     * {@inheritdoc}
     */
    public function handle($event): void
    {
        if (!\class_exists(\Spatie\LaravelIgnition\IgnitionServiceProvider::class)) {
            return;
        }

        if ($event instanceof WithApplication) {
            $app = $event->application();

            /** @see \Spatie\LaravelIgnition\IgnitionServiceProvider::resetFlareAndLaravelIgnition */
            foreach ([
                         \Spatie\Ignition\Ignition::class,
                         \Spatie\LaravelIgnition\Support\SentReports::class,
                         \Spatie\LaravelIgnition\Recorders\DumpRecorder\DumpRecorder::class,
                         \Spatie\LaravelIgnition\Recorders\LogRecorder\LogRecorder::class,
                         \Spatie\LaravelIgnition\Recorders\QueryRecorder\QueryRecorder::class,
                         \Spatie\LaravelIgnition\Recorders\JobRecorder\JobRecorder::class,
                     ] as $abstract) {
                if ($app->resolved($abstract)) {
                    $instance = $app->make($abstract);

                    if (!\is_object($instance)) {
                        continue;
                    }

                    foreach (['reset', 'clear'] as $method_name) {
                        if ($this->invokeMethod($instance, $method_name)) {
                            break;
                        }
                    }
                }
            }
        }
    }
}
