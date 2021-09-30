<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel\Tests\Unit\Listeners;

use Tightenco\Ziggy\BladeRouteGenerator;
use Spiral\RoadRunnerLaravel\Listeners\ResetZiggyListener;

/**
 * @covers \Spiral\RoadRunnerLaravel\Listeners\ResetZiggyListener
 */
class ResetZiggyListenerTest extends AbstractListenerTestCase
{
    /**
     * {@inheritdoc}
     */
    public function testHandle(): void
    {
        BladeRouteGenerator::$generated = true;

        $this->listenerFactory()->handle(new \stdClass());

        $this->assertFalse(BladeRouteGenerator::$generated);
    }

    /**
     * @return ResetZiggyListener
     */
    protected function listenerFactory(): ResetZiggyListener
    {
        return new ResetZiggyListener();
    }
}
