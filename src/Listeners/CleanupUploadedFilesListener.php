<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel\Listeners;

use Spiral\RoadRunnerLaravel\Events\Contracts\WithHttpRequest;

/**
 * @link https://github.com/spiral/roadrunner-laravel/issues/84
 */
class CleanupUploadedFilesListener implements ListenerInterface
{
    /**
     * {@inheritdoc}
     */
    public function handle($event): void
    {
        if ($event instanceof WithHttpRequest) {
            foreach ($event->httpRequest()->files->all() as $file) {
                if ($file instanceof \SplFileInfo) {
                    if (\is_string($path = $file->getRealPath()) && \is_file($path)) {
                        \unlink($path);
                    }
                }
            }
        }
    }
}
