<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel\Tests\Unit\Events;

use Spiral\RoadRunnerLaravel\Events\Contracts;
use Spiral\RoadRunnerLaravel\Events\BeforeLoopIterationEvent;

/**
 * @covers \Spiral\RoadRunnerLaravel\Events\BeforeLoopIterationEvent
 */
class BeforeLoopIterationEventTest extends \Spiral\RoadRunnerLaravel\Tests\AbstractTestCase
{
    /**
     * @return void
     */
    public function testInterfacesImplementation(): void
    {
        foreach ([Contracts\WithApplication::class, Contracts\WithServerRequest::class] as $interface) {
            $this->assertContains(
                $interface,
                \class_implements(BeforeLoopIterationEvent::class),
                "Event does not implements [{$interface}]"
            );
        }
    }

    /**
     * @return void
     */
    public function testConstructor(): void
    {
        $event = new BeforeLoopIterationEvent(
            $this->app,
            $request = (new \Nyholm\Psr7\Factory\Psr17Factory())->createServerRequest('GET', 'https://testing')
        );

        $this->assertSame($this->app, $event->application());
        $this->assertSame($request, $event->serverRequest());
    }
}
