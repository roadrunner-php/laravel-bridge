<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel\Tests\Unit\Listeners;

use Mockery as m;
use Spiral\RoadRunner\Http\PSR7Worker;
use Spiral\RoadRunnerLaravel\Listeners\StopWorkerListener;
use Spiral\RoadRunnerLaravel\Events\Contracts\WithApplication;

/**
 * @covers \Spiral\RoadRunnerLaravel\Listeners\StopWorkerListener<extended>
 */
class StopWorkerListenerTest extends AbstractListenerTestCase
{
    /**
     * {@inheritdoc}
     */
    public function testHandle(): void
    {
        $worker = m::mock()->shouldReceive('stop')->once()->getMock();

        $psr7_client_mock = new class($worker) {
            protected $worker;

            public function __construct($worker)
            {
                $this->worker = $worker;
            }

            public function getWorker()
            {
                return $this->worker;
            }
        };

        $this->app->instance(PSR7Worker::class, $psr7_client_mock);

        $event_mock = m::mock(WithApplication::class)
            ->makePartial()
            ->expects('application')
            ->once()
            ->andReturn($this->app)
            ->getMock();

        $this->listenerFactory()->handle($event_mock);
    }

    /**
     * {@inheritdoc}
     */
    protected function listenerFactory()
    {
        return new StopWorkerListener();
    }
}
