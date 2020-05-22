<?php

declare(strict_types = 1);

namespace Spiral\RoadRunnerLaravel\Tests\Listeners;

use Mockery as m;
use Spiral\RoadRunnerLaravel\Listeners\ResetSessionListener;
use Spiral\RoadRunnerLaravel\Events\Contracts\WithApplication;

/**
 * @covers \Spiral\RoadRunnerLaravel\Listeners\ResetSessionListener<extended>
 */
class ResetSessionListenerTest extends AbstractListenerTestCase
{
    /**
     * {@inheritdoc}
     */
    public function testHandle(): void
    {
        /** @var \Illuminate\Session\SessionManager $session */
        $session = $this->app->make($session_abstract = 'session');

        $session_mock = m::mock($session)
            ->makePartial()
            ->expects('driver')
            ->withNoArgs()
            ->andReturn(
                m::mock($session->driver())
                    ->makePartial()
                    ->expects('flush')
                    ->withNoArgs()
                    ->getMock()
                    ->expects('regenerate')
                    ->withNoArgs()
                    ->getMock()
            )
            ->getMock();

        $this->app->instance($session_abstract, $session_mock);

        /** @var m\MockInterface|WithApplication $event_mock */
        $event_mock = m::mock(WithApplication::class)
            ->makePartial()
            ->expects('application')
            ->andReturn($this->app)
            ->getMock();

        $this->listenerFactory()->handle($event_mock);
    }

    /**
     * @return ResetSessionListener
     */
    protected function listenerFactory(): ResetSessionListener
    {
        return new ResetSessionListener;
    }
}
