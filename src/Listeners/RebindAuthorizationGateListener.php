<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel\Listeners;

use Illuminate\Contracts\Auth\Access\Gate;
use Spiral\RoadRunnerLaravel\Events\Contracts\WithApplication;

/**
 * @link https://github.com/laravel/octane/blob/1.x/src/Listeners/GiveNewApplicationInstanceToAuthorizationGate.php
 */
class RebindAuthorizationGateListener implements ListenerInterface
{
    use Traits\InvokerTrait;

    /**
     * {@inheritdoc}
     */
    public function handle($event): void
    {
        if ($event instanceof WithApplication) {
            $app = $event->application();

            if (! $app->resolved($gate_abstract = Gate::class)) {
                return;
            }

            /** @var \Illuminate\Auth\Access\Gate $gate */
            $gate = $app->make($gate_abstract);

            $gate->setContainer($app);
        }
    }
}
