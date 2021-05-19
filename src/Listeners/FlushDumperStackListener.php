<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel\Listeners;

use Spiral\RoadRunnerLaravel\Dumper\Stack\StackInterface;
use Spiral\RoadRunnerLaravel\Events\Contracts\WithApplication;

/**
 * @see \Spiral\RoadRunnerLaravel\Dumper\Dumper
 */
class FlushDumperStackListener implements ListenerInterface
{
    /**
     * {@inheritdoc}
     */
    public function handle($event): void
    {
        if ($event instanceof WithApplication) {
            $app = $event->application();

            if (!$app->bound($stack_abstract = StackInterface::class)) {
                return;
            }

            /** @var StackInterface $stack */
            $stack = $app->make($stack_abstract);

            if ($stack->count() > 0) {
                $stack->flush();
            }
        }
    }
}
