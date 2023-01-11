<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel\Listeners;

use Illuminate\Support\Str;

/**
 * @link https://github.com/laravel/octane/blob/1.x/src/Listeners/FlushStrCache.php
 */
class FlushStrCacheListener implements ListenerInterface
{
    use Traits\InvokerTrait;

    /**
     * {@inheritdoc}
     */
    public function handle($event): void
    {
        Str::flushCache();
    }
}
