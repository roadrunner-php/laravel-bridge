<?php

declare(strict_types = 1);

namespace Spiral\RoadRunnerLaravel\Tests\Events;

use Spiral\RoadRunnerLaravel\Events\AfterLoopStoppedEvent;
use Spiral\RoadRunnerLaravel\Events\Contracts\WithApplication;

/**
 * @covers \Spiral\RoadRunnerLaravel\Events\AfterLoopStoppedEvent<extended>
 */
class AfterLoopStoppedEventTest extends AbstractEventTestCase
{
    /**
     * @var string[]
     */
    protected $required_interfaces = [
        WithApplication::class,
    ];

    /**
     * @var string
     */
    protected $event_class = AfterLoopStoppedEvent::class;

    /**
     * {@inheritdoc}
     */
    public function testConstructor(): void
    {
        $event = new AfterLoopStoppedEvent($this->app);

        $this->assertSame($this->app, $event->application());
    }
}
