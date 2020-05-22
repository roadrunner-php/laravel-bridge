<?php

declare(strict_types = 1);

namespace Spiral\RoadRunnerLaravel\Listeners;

use Spiral\RoadRunnerLaravel\Events\Contracts\WithApplication;
use Spiral\RoadRunnerLaravel\Events\Contracts\WithHttpRequest;

/**
 * @link https://github.com/swooletw/laravel-swoole/blob/master/src/Server/Resetters/BindRequest.php
 */
class BindRequestListener implements ListenerInterface
{
    /**
     * {@inheritdoc}
     */
    public function handle($event): void
    {
        if ($event instanceof WithApplication && $event instanceof WithHttpRequest) {
            $event->application()->instance('request', $event->httpRequest());
        }
    }
}
