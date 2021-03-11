<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel\Tests\Unit\Events;

use Illuminate\Http\Request;
use Spiral\RoadRunnerLaravel\Events\Contracts;
use Spiral\RoadRunnerLaravel\Events\BeforeRequestHandlingEvent;

/**
 * @covers \Spiral\RoadRunnerLaravel\Events\BeforeRequestHandlingEvent<extended>
 */
class BeforeRequestHandlingEventTest extends \Spiral\RoadRunnerLaravel\Tests\AbstractTestCase
{
    /**
     * @return void
     */
    public function testInterfacesImplementation(): void
    {
        foreach ($required_interfaces = [
            Contracts\WithApplication::class,
            Contracts\WithHttpRequest::class,
        ] as $interface) {
            $this->assertContains(
                $interface,
                \class_implements(BeforeRequestHandlingEvent::class),
                "Event does not implements [{$interface}]"
            );
        }
    }

    /**
     * @return void
     */
    public function testConstructor(): void
    {
        $event = new BeforeRequestHandlingEvent(
            $this->app,
            $request = Request::create('/')
        );

        $this->assertSame($this->app, $event->application());
        $this->assertSame($request, $event->httpRequest());
    }
}
