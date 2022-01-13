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

            /**
             * Method `setApplication` for the FilesystemManager available since Laravel v8.77.0.
             *
             * @link https://github.com/laravel/framework/blob/v8.77.0/src/Illuminate/Filesystem/FilesystemManager.php#L395-L400
             * @see  \Illuminate\Filesystem\FilesystemManager::setApplication
             */
            if (! $this->invokeMethod($filesystem_manager, 'setApplication', $app)) {
                $this->setProperty($filesystem_manager, 'app', $app);
            }
        }
    }
}
