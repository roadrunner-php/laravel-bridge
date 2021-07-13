<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel\Tests\Unit\Listeners;

use Mockery as m;
use Spiral\RoadRunnerLaravel\Listeners\SetupTelescopeListener;
use Spiral\RoadRunnerLaravel\Events\Contracts\WithApplication;

/**
 * @covers \Spiral\RoadRunnerLaravel\Listeners\SetupTelescopeListener
 */
class SetupTelescopeListenerTest extends AbstractListenerTestCase
{
    /**
     * {@inheritdoc}
     */
    public function testHandle(): void
    {
        /** @var m\MockInterface|WithApplication $event_mock */
        $event_mock = m::mock(WithApplication::class)
            ->makePartial()
            ->expects('application')
            ->andReturn($this->app)
            ->getMock();

        $this->listenerFactory()->handle($event_mock);

        $this->markTestIncomplete('Implement me');
    }

    /**
     * @return SetupTelescopeListener
     */
    protected function listenerFactory(): SetupTelescopeListener
    {
        return new SetupTelescopeListener();
    }
}
