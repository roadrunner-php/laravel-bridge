<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel\Tests\Unit\Listeners;

use Mockery as m;
use Spiral\RoadRunnerLaravel\Dumper\Stack\StackInterface;
use Spiral\RoadRunnerLaravel\Events\Contracts\WithApplication;
use Spiral\RoadRunnerLaravel\Listeners\FlushDumperStackListener;

/**
 * @covers \Spiral\RoadRunnerLaravel\Listeners\FlushDumperStackListener
 */
class FlushDumperStackListenerTest extends AbstractListenerTestCase
{
    /**
     * {@inheritdoc}
     */
    public function testHandle(): void
    {
        /** @var StackInterface $stack */
        $stack = $this->app->make(StackInterface::class);

        $stack->push('foo');

        /** @var m\MockInterface|WithApplication $event_mock */
        $event_mock = m::mock(WithApplication::class)
            ->makePartial()
            ->expects('application')
            ->andReturn($this->app)
            ->getMock();

        $this->assertNotSame(0, $stack->count());

        $this->listenerFactory()->handle($event_mock);

        $this->assertSame(0, $stack->count());
    }

    /**
     * @return FlushDumperStackListener
     */
    protected function listenerFactory(): FlushDumperStackListener
    {
        return new FlushDumperStackListener();
    }
}
