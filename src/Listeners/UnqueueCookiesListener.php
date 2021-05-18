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

                /**
                 * Method `flushQueuedCookies` for the CookieJar available since Laravel v8.34.0.
                 *
                 * @link https://git.io/Jsuph Pull request
                 * @link https://git.io/Jszvy Source code (v8.34.0)
                 * @see  \Illuminate\Cookie\CookieJar::flushQueuedCookies
                 */
                if (!$this->invokeMethod($cookies, 'flushQueuedCookies')) {
                    foreach ($cookies->getQueuedCookies() as $_ => $value) { // the "old" way
                        $cookies->unqueue($value->getName());
                    }
                }
            }
        }
    }
}
