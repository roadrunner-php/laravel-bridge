<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel\Tests\Unit\Listeners;

use Mockery as m;
use Illuminate\Contracts\Auth\Access\Gate;
use Spiral\RoadRunnerLaravel\Events\Contracts\WithApplication;
use Spiral\RoadRunnerLaravel\Listeners\RebindAuthorizationGateListener;

/**
 * @covers \Spiral\RoadRunnerLaravel\Listeners\RebindAuthorizationGateListener
 */
class RebindAuthorizationGateListenerTest extends AbstractListenerTestCase
{
    /**
     * {@inheritdoc}
     */
    public function testHandle(): void
    {
        $app_clone = clone $this->app;

        /** @var Gate $gate */
        $gate = $this->app->make(Gate::class);

        $this->setProperty($gate, $container_prop = 'container', $app_clone);

        /** @var m\MockInterface|WithApplication $event_mock */
        $event_mock = m::mock(WithApplication::class)
            ->makePartial()
            ->expects('application')
            ->andReturn($this->app)
            ->getMock();

        $this->listenerFactory()->handle($event_mock);

        $this->assertSame($this->app, $this->getProperty($gate, $container_prop));
    }

    /**
     * @return RebindAuthorizationGateListener
     */
    protected function listenerFactory(): RebindAuthorizationGateListener
    {
        return new RebindAuthorizationGateListener();
    }
}
