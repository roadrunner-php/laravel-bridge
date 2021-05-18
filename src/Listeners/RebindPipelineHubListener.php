<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel\Listeners;

use Illuminate\Contracts\Pipeline\Hub;
use Spiral\RoadRunnerLaravel\Events\Contracts\WithApplication;

/**
 * @link https://github.com/laravel/octane/blob/1.x/src/Listeners/GiveNewApplicationInstanceToPipelineHub.php
 */
class RebindPipelineHubListener implements ListenerInterface
{
    use Traits\InvokerTrait;

    /**
     * {@inheritdoc}
     */
    public function handle($event): void
    {
        if ($event instanceof WithApplication) {
            $app = $event->application();

            if (!$app->resolved($hub_abstract = Hub::class)) {
                return;
            }

            /** @var Hub $hub */
            $hub = $app->make($hub_abstract);

            /**
             * Method `setContainer` for the Hub available since Laravel v8.35.0.
             *
             * @link https://git.io/Jszic Source code (v8.35.0)
             * @see  \Illuminate\Pipeline\Hub::setContainer
             */
            if (!$this->invokeMethod($hub, 'setContainer', $app)) {
                $this->setProperty($hub, 'container', $app);
            }
        }
    }
}
