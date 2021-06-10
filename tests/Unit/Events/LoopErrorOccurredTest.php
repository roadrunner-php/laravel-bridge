<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel\Tests\Unit\Events;

use Spiral\RoadRunnerLaravel\Events\Contracts;
use Spiral\RoadRunnerLaravel\Events\LoopErrorOccurredEvent;

/**
 * @covers \Spiral\RoadRunnerLaravel\Events\LoopErrorOccurredEvent
 */
class LoopErrorOccurredTest extends \Spiral\RoadRunnerLaravel\Tests\AbstractTestCase
{
    /**
     * @return void
     */
    public function testInterfacesImplementation(): void
    {
        $expected = [
            Contracts\WithApplication::class,
            Contracts\WithException::class,
            Contracts\WithServerRequest::class,
        ];

        foreach ($expected as $interface) {
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
            $request = (new \Nyholm\Psr7\Factory\Psr17Factory())->createServerRequest('GET', 'https://testing'),
            $exception = new \Exception('foo')
        );

        $this->assertSame($this->app, $event->application());
        $this->assertSame($exception, $event->exception());
        $this->assertSame($request, $event->serverRequest());
    }
}
