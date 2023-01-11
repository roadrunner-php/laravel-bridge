<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel\Listeners;

use Spiral\RoadRunnerLaravel\Events\Contracts\WithApplication;

/**
 * @link https://github.com/laravel/octane/blob/1.x/src/Listeners/GiveNewApplicationInstanceToFilesystemManager.php
 */
class RebindFilesystemManagerListener implements ListenerInterface
{
    use Traits\InvokerTrait;

    /**
     * {@inheritdoc}
     */
    public function handle($event): void
    {
        if ($event instanceof WithApplication) {
            $app = $event->application();

            if (!$app->resolved($filesystem_abstract = 'filesystem')) {
                return;
            }

            /** @var \Illuminate\Filesystem\FilesystemManager $filesystem_manager */
            $filesystem_manager = $app->make($filesystem_abstract);

            $filesystem_manager->setApplication($app);
        }
    }
}
