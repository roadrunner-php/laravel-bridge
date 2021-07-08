<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel\Tests\Unit\Listeners;

use Mockery as m;
use Spiral\RoadRunnerLaravel\Events\Contracts\WithApplication;
use Spiral\RoadRunnerLaravel\Listeners\FlushLogContextListener;

/**
 * @covers \Spiral\RoadRunnerLaravel\Listeners\FlushLogContextListener
 */
class FlushLogContextListenerTest extends AbstractListenerTestCase
{
    /**
     * {@inheritdoc}
     */
    public function testHandle(): void
    {
        /** @var \Illuminate\Log\LogManager $log_manager */
        $log_manager = $this->app->make('log');

        $logger = $log_manager->driver();

        if (! \method_exists($logger, 'withoutContext')) {
            $this->markTestSkipped('Current illuminate/log package version does now supports ::withoutContext()');
        }

        $this->app->instance('log', m::mock($log_manager)
            ->makePartial()
            ->expects('driver')
            ->withNoArgs()
            ->andReturn(
                m::mock($logger)
                    ->makePartial()
                    ->expects('withoutContext')
                    ->withNoArgs()
                    ->andReturn($logger)
                    ->getMock()
            )
            ->getMock());

        /** @var m\MockInterface|WithApplication $event_mock */
        $event_mock = m::mock(WithApplication::class)
            ->makePartial()
            ->expects('application')
            ->andReturn($this->app)
            ->getMock();

        $this->listenerFactory()->handle($event_mock);
    }

    /**
     * @return FlushLogContextListener
     */
    protected function listenerFactory(): FlushLogContextListener
    {
        return new FlushLogContextListener();
    }
}
