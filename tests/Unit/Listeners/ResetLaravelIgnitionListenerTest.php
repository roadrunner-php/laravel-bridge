<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel\Tests\Unit\Listeners;

use Mockery as m;
use Spatie\LaravelIgnition\Recorders;
use Spiral\RoadRunnerLaravel\Events\Contracts\WithApplication;
use Spiral\RoadRunnerLaravel\Listeners\ResetLaravelIgnitionListener;

/**
 * @covers \Spiral\RoadRunnerLaravel\Listeners\ResetLaravelIgnitionListener
 */
class ResetLaravelIgnitionListenerTest extends AbstractListenerTestCase
{
    /**
     * {@inheritdoc}
     */
    public function testHandle(): void
    {
        $this->spy(\Spatie\Ignition\Ignition::class, function (m\MockInterface $m) {
            $m->shouldReceive('reset')->once();
        });

        $this->spy(\Spatie\LaravelIgnition\Support\SentReports::class, function (m\MockInterface $m) {
            $m->shouldReceive('clear')->once();
        });

        $this->spy(Recorders\DumpRecorder\DumpRecorder::class, function (m\MockInterface $m) {
            $m->shouldReceive('reset')->once();
        });

        $this->spy(Recorders\LogRecorder\LogRecorder::class, function (m\MockInterface $m) {
            $m->shouldReceive('reset')->once();
        });

        $this->spy(Recorders\QueryRecorder\QueryRecorder::class, function (m\MockInterface $m) {
            $m->shouldReceive('reset')->once();
        });

        $this->spy(Recorders\JobRecorder\JobRecorder::class, function (m\MockInterface $m) {
            $m->shouldReceive('reset')->once();
        });

        /** @var m\MockInterface|WithApplication $event_mock */
        $event_mock = m::mock(WithApplication::class)
            ->makePartial()
            ->expects('application')
            ->andReturn($this->app)
            ->getMock();

        $this->listenerFactory()->handle($event_mock);
    }

    /**
     * @return ResetLaravelIgnitionListener
     */
    protected function listenerFactory(): ResetLaravelIgnitionListener
    {
        return new ResetLaravelIgnitionListener();
    }
}
