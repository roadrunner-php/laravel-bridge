<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel\Tests\Unit\Listeners;

use Spiral\RoadRunnerLaravel\Listeners\RebindDatabaseSessionHandlerListener;

/**
 * @covers \Spiral\RoadRunnerLaravel\Listeners\RebindDatabaseSessionHandlerListener
 */
class RebindDatabaseSessionHandlerListenerTest extends AbstractListenerTestCase
{
    /**
     * {@inheritdoc}
     */
    public function testHandle(): void
    {
        $this->markTestSkipped('Not implemented yet');
    }

    /**
     * @return RebindDatabaseSessionHandlerListener
     */
    protected function listenerFactory(): RebindDatabaseSessionHandlerListener
    {
        return new RebindDatabaseSessionHandlerListener();
    }
}
