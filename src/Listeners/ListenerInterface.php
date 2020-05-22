<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel\Listeners;

interface ListenerInterface
{
    /**
     * Handle the event.
     *
     * @param string|object $event
     *
     * @return void
     */
    public function handle($event): void;
}
