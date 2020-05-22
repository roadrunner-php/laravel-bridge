<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel\Tests\Events;

use Spiral\RoadRunnerLaravel\Events\BeforeLoopStartedEvent;
use Spiral\RoadRunnerLaravel\Events\Contracts\WithApplication;

/**
 * @covers \Spiral\RoadRunnerLaravel\Events\BeforeLoopStartedEvent<extended>
 */
class BeforeLoopStartedEventTest extends AbstractEventTestCase
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
    protected $event_class = BeforeLoopStartedEvent::class;

    /**
     * {@inheritdoc}
     */
    public function testConstructor(): void
    {
        $event = new BeforeLoopStartedEvent($this->app);

        $this->assertSame($this->app, $event->application());
    }
}
