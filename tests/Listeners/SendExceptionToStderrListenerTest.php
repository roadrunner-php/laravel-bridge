<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel\Tests\Listeners;

use Spiral\RoadRunnerLaravel\Listeners\SendExceptionToStderrListener;

/**
 * @covers \Spiral\RoadRunnerLaravel\Listeners\SendExceptionToStderrListener<extended>
 */
class SendExceptionToStderrListenerTest extends AbstractListenerTestCase
{
    public function testHandle(): void
    {
        $this->listenerFactory()->handle(new \stdClass());

        $this->markTestIncomplete('There is no legal way for handle method testing.');
    }

    protected function listenerFactory()
    {
        return new SendExceptionToStderrListener();
    }
}
