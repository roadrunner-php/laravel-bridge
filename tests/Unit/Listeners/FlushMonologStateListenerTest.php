<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel\Tests\Unit\Listeners;

use Mockery as m;
use Spiral\RoadRunnerLaravel\Events\Contracts\WithApplication;
use Spiral\RoadRunnerLaravel\Listeners\FlushMonologStateListener;

/**
 * @covers \Spiral\RoadRunnerLaravel\Listeners\FlushMonologStateListener
 */
class FlushMonologStateListenerTest extends AbstractListenerTestCase
{
    /**
     * {@inheritdoc}
     */
    public function testHandle(): void
    {
        /** @var \Illuminate\Log\LogManager $log_manager */
        $log_manager = $this->app->make('log');

        /** @var \Illuminate\Log\Logger $driver */
        $driver = $log_manager->driver();

        /** @var \Monolog\Logger $logger */
        $logger = $driver->getLogger();

        $this->app->instance('log', m::mock($log_manager)
            ->makePartial()
            ->expects('getChannels')
            ->withNoArgs()
            ->andReturns([
                m::mock($driver)
                    ->makePartial()
                    ->expects('getLogger')
                    ->withNoArgs()
                    ->andReturn(
                        m::mock($logger)
                            ->makePartial()
                            ->expects('reset')
                            ->once()
                            ->withNoArgs()
                            ->getMock(),
                    )
                    ->getMock(),
            ])
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
     * @return FlushMonologStateListener
     */
    protected function listenerFactory(): FlushMonologStateListener
    {
        return new FlushMonologStateListener();
    }
}
