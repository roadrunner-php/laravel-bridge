<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel\Listeners;

use Tightenco\Ziggy\BladeRouteGenerator;

/**
 * Target package: <https://github.com/tighten/ziggy>.
 */
class ResetZiggyListener implements ListenerInterface
{
    use Traits\InvokerTrait;

    /**
     * {@inheritdoc}
     */
    public function handle($event): void
    {
        if (!\class_exists(BladeRouteGenerator::class)) {
            return;
        }

        BladeRouteGenerator::$generated = false;
    }
}
