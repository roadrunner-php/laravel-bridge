<?php

declare(strict_types = 1);

namespace Spiral\RoadRunnerLaravel\Listeners;

use Spiral\RoadRunnerLaravel\Events\Contracts\WithApplication;

/**
 * @link https://github.com/swooletw/laravel-swoole/blob/master/src/Server/Resetters/RebindViewContainer.php
 */
class RebindViewListener implements ListenerInterface
{
    /**
     * {@inheritdoc}
     */
    public function handle($event): void
    {
        if ($event instanceof WithApplication) {
            $app = $event->application();

            /** @var \Illuminate\View\Factory $view */
            $view = $app->make('view');

            $closure = function () use ($app) {
                $this->{'container'}     = $app;
                $this->{'shared'}['app'] = $app;
            };

            // Black magic in action
            $resetView = $closure->bindTo($view, $view);
            $resetView();
        }
    }
}
