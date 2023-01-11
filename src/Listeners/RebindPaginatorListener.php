<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel\Listeners;

use Illuminate\Pagination\PaginationState;
use Spiral\RoadRunnerLaravel\Events\Contracts\WithApplication;

/**
 * @link https://github.com/laravel/octane/blob/1.x/src/Listeners/GiveNewRequestInstanceToPaginator.php
 */
class RebindPaginatorListener implements ListenerInterface
{
    /**
     * {@inheritdoc}
     */
    public function handle($event): void
    {
        if ($event instanceof WithApplication) {
            PaginationState::resolveUsing($event->application());
        }
    }
}
