<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel\Tests\Listeners;

use Spiral\RoadRunnerLaravel\Listeners\RunGarbageCollectorListener;

/**
 * @covers \Spiral\RoadRunnerLaravel\Listeners\RunGarbageCollectorListener<extended>
 */
class RunGarbageCollectorListenerTest extends AbstractListenerTestCase
{
    /**
     * {@inheritdoc}
     */
    public function testHandle(): void
    {
        $this->listenerFactory()->handle(new \stdClass());

        $this->markTestIncomplete('There is no legal way for handle method testing.');
    }

    /**
     * @return RunGarbageCollectorListener
     */
    protected function listenerFactory(): RunGarbageCollectorListener
    {
        return new RunGarbageCollectorListener();
    }
}
