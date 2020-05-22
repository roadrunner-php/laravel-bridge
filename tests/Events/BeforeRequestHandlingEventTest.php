<?php

declare(strict_types = 1);

namespace Spiral\RoadRunnerLaravel\Tests\Events;

use Illuminate\Http\Request;
use Spiral\RoadRunnerLaravel\Events\Contracts\WithApplication;
use Spiral\RoadRunnerLaravel\Events\Contracts\WithHttpRequest;
use Spiral\RoadRunnerLaravel\Events\BeforeRequestHandlingEvent;

/**
 * @covers \Spiral\RoadRunnerLaravel\Events\BeforeRequestHandlingEvent<extended>
 */
class BeforeRequestHandlingEventTest extends AbstractEventTestCase
{
    /**
     * @var string[]
     */
    protected $required_interfaces = [
        WithApplication::class,
        WithHttpRequest::class,
    ];

    /**
     * @var string
     */
    protected $event_class = BeforeRequestHandlingEvent::class;

    /**
     * {@inheritdoc}
     */
    public function testConstructor(): void
    {
        $event = new BeforeRequestHandlingEvent(
            $this->app, $request = Request::create('/')
        );

        $this->assertSame($this->app, $event->application());
        $this->assertSame($request, $event->httpRequest());
    }
}
