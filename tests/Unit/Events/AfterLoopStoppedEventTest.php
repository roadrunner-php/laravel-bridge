<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel\Tests\Unit\Events;

use Spiral\RoadRunnerLaravel\Events\Contracts;
use Spiral\RoadRunnerLaravel\Events\AfterLoopStoppedEvent;

/**
 * @covers \Spiral\RoadRunnerLaravel\Events\AfterLoopStoppedEvent<extended>
 */
class AfterLoopStoppedEventTest extends \Spiral\RoadRunnerLaravel\Tests\AbstractTestCase
{
    /**
     * @return void
     */
    public function testInterfacesImplementation(): void
    {
        foreach ($required_interfaces = [
            Contracts\WithApplication::class,
        ] as $interface) {
            $this->assertContains(
                $interface,
                \class_implements(AfterLoopStoppedEvent::class),
                "Event does not implements [{$interface}]"
            );
        }
    }

    /**
     * @return void
     */
    public function testConstructor(): void
    {
        $event = new AfterLoopStoppedEvent($this->app);

        $this->assertSame($this->app, $event->application());
    }
}
