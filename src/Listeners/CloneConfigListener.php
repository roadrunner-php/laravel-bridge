<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel\Listeners;

use Illuminate\Config\Repository as ConfigRepository;
use Spiral\RoadRunnerLaravel\Events\Contracts\WithApplication;

/**
 * @link https://github.com/swooletw/laravel-swoole/blob/master/src/Server/Resetters/ResetConfig.php
 */
class CloneConfigListener implements ListenerInterface
{
    /**
     * {@inheritdoc}
     */
    public function handle($event): void
    {
        if ($event instanceof WithApplication) {
            $app = $event->application();

            $app->instance('config', clone $app->make(ConfigRepository::class));
        }
    }
}
