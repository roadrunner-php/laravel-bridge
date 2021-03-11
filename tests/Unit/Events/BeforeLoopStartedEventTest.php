<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel\Tests\Unit\Events;

use Spiral\RoadRunnerLaravel\Events\Contracts;
use Spiral\RoadRunnerLaravel\Events\BeforeLoopStartedEvent;

/**
 * @covers \Spiral\RoadRunnerLaravel\Events\BeforeLoopStartedEvent<extended>
 */
class BeforeLoopStartedEventTest extends \Spiral\RoadRunnerLaravel\Tests\AbstractTestCase
{
    /**
     * @return void
     */
    public function testInterfacesImplementation(): void
    {
        foreach ([Contracts\WithApplication::class] as $interface) {
            $this->assertContains(
                $interface,
                \class_implements(BeforeLoopStartedEvent::class),
                "Event does not implements [{$interface}]"
            );
        }
    }

    /**
     * @return void
     */
    public function testConstructor(): void
    {
        $event = new BeforeLoopStartedEvent($this->app);

        $this->assertSame($this->app, $event->application());
    }
}
