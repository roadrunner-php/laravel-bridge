<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel\Tests\Events;

use Laminas\Diactoros\ServerRequest;
use Spiral\RoadRunnerLaravel\Events\Contracts;
use Spiral\RoadRunnerLaravel\Tests\AbstractTestCase;
use Spiral\RoadRunnerLaravel\Events\LoopErrorOccurredEvent;

/**
 * @covers \Spiral\RoadRunnerLaravel\Events\LoopErrorOccurredEvent<extended>
 */
class LoopErrorOccurredTest extends AbstractTestCase
{
    /**
     * @return void
     */
    public function testInterfacesImplementation(): void
    {
        foreach ($required_interfaces = [
            Contracts\WithApplication::class,
            Contracts\WithException::class,
            Contracts\WithServerRequest::class,
        ] as $interface) {
            $this->assertContains(
                $interface,
                \class_implements(LoopErrorOccurredEvent::class),
                "Event does not implements [{$interface}]"
            );
        }
    }

    /**
     * @return void
     */
    public function testConstructor(): void
    {
        $event = new LoopErrorOccurredEvent(
            $this->app,
            $request = new ServerRequest(),
            $exception = new \Exception('foo')
        );

        $this->assertSame($this->app, $event->application());
        $this->assertSame($exception, $event->exception());
        $this->assertSame($request, $event->serverRequest());
    }
}
