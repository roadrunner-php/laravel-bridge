<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel\Listeners;

use Illuminate\Contracts\Pipeline\Hub;
use Spiral\RoadRunnerLaravel\Events\Contracts\WithApplication;

/**
 * @link https://github.com/laravel/octane/blob/1.x/src/Listeners/GiveNewApplicationInstanceToValidationFactory.php
 */
class RebindValidationFactoryListener implements ListenerInterface
{
    use Traits\InvokerTrait;

    /**
     * {@inheritdoc}
     */
    public function handle($event): void
    {
        if ($event instanceof WithApplication) {
            $app = $event->application();

            if (!$app->resolved($validator_abstract = 'validator')) {
                return;
            }

            /** @var \Illuminate\Validation\Factory $validator */
            $validator = $app->make($validator_abstract);

            $validator->setContainer($app);
        }
    }
}
