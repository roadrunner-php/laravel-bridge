<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel\Listeners;

use Illuminate\Support\Str;

/**
 * @link https://github.com/laravel/octane/blob/1.x/src/Listeners/FlushStrCache.php
 */
class FlushStrCacheListener implements ListenerInterface
{
    /**
     * {@inheritdoc}
     */
    public function handle($event): void
    {
        /**
         * Method `flushCache` for the Str available since Laravel v8.81.0.
         *
         * @link https://github.com/illuminate/support/blob/v8.81.0/Str.php#L994
         * @see  \Illuminate\Support\Str::flushCache
         */
        if (\method_exists(Str::class, $method_name = 'flushCache')) {
            Str::{$method_name}();
        }
    }
}
