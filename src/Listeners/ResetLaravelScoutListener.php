<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel\Listeners;

use Laravel\Scout\EngineManager as ScoutEngineManager;
use Spiral\RoadRunnerLaravel\Events\Contracts\WithApplication;

/**
 * Target package: <https://github.com/laravel/scout/>.
 *
 * @link https://github.com/laravel/octane/blob/1.x/src/Listeners/PrepareScoutForNextOperation.php
 */
class ResetLaravelScoutListener implements ListenerInterface
{
    use Traits\InvokerTrait;

    /**
     * {@inheritdoc}
     */
    public function handle($event): void
    {
        if ($event instanceof WithApplication) {
            $app = $event->application();

            if (!$app->resolved($scout_abstract = ScoutEngineManager::class)) {
                return;
            }

            /** @var ScoutEngineManager $scout */
            $scout = $app->make($scout_abstract);

            /** @see \Laravel\Scout\EngineManager::forgetEngines() */
            if (!$this->invokeMethod($scout, 'forgetEngines')) {
                $this->setProperty($scout, 'drivers', []);
            }
        }
    }
}
