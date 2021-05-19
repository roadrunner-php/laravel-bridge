<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel\Tests\Unit\Listeners;

use Spiral\RoadRunnerLaravel\Listeners\ListenerInterface;
use Spiral\RoadRunnerLaravel\Listeners\SendExceptionToStderrListener;

/**
 * @covers \Spiral\RoadRunnerLaravel\Listeners\SendExceptionToStderrListener
 */
class SendExceptionToStderrListenerTest extends AbstractListenerTestCase
{
    /**
     * @return void
     */
    public function testHandle(): void
    {
        $this->listenerFactory()->handle(new \stdClass());

        $this->markTestIncomplete('There is no legal way for handle method testing.');
    }

    /**
     * @return SendExceptionToStderrListener
     */
    protected function listenerFactory(): ListenerInterface
    {
        return new SendExceptionToStderrListener();
    }
}
