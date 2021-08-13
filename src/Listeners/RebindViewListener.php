<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel\Listeners;

use Spiral\RoadRunnerLaravel\Events\Contracts\WithApplication;

/**
 * @link https://github.com/swooletw/laravel-swoole/blob/master/src/Server/Resetters/RebindViewContainer.php
 * @link https://github.com/laravel/octane/blob/1.x/src/Listeners/GiveNewApplicationInstanceToViewFactory.php
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

            if (!$app->resolved($view_abstract = 'view')) {
                return;
            }

            /** @var \Illuminate\View\Factory $view */
            $view = $app->make($view_abstract);

            $view->setContainer($app);
            $view->share('app', $app);
        }
    }
}
