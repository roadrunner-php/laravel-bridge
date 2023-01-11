<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel\Listeners;

use Spiral\RoadRunnerLaravel\Events\Contracts\WithApplication;

/**
 * @link https://github.com/swooletw/laravel-swoole/blob/master/src/Server/Resetters/ResetCookie.php
 */
class UnqueueCookiesListener implements ListenerInterface
{
    use Traits\InvokerTrait;

    /**
     * {@inheritdoc}
     */
    public function handle($event): void
    {
        if ($event instanceof WithApplication) {
            $app = $event->application();

            if ($app->bound($cookie_abstract = 'cookie')) {
                /** @var \Illuminate\Cookie\CookieJar $cookies */
                $cookies = $app->make($cookie_abstract);

                $cookies->flushQueuedCookies();
            }
        }
    }
}
