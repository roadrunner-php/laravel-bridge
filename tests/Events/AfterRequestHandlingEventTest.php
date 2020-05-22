<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel\Tests\Events;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Spiral\RoadRunnerLaravel\Events\AfterRequestHandlingEvent;
use Spiral\RoadRunnerLaravel\Events\Contracts\WithApplication;
use Spiral\RoadRunnerLaravel\Events\Contracts\WithHttpRequest;
use Spiral\RoadRunnerLaravel\Events\Contracts\WithHttpResponse;

/**
 * @covers \Spiral\RoadRunnerLaravel\Events\AfterRequestHandlingEvent<extended>
 */
class AfterRequestHandlingEventTest extends AbstractEventTestCase
{
    /**
     * @var string[]
     */
    protected $required_interfaces = [
        WithApplication::class,
        WithHttpRequest::class,
        WithHttpResponse::class,
    ];

    /**
     * @var string
     */
    protected $event_class = AfterRequestHandlingEvent::class;

    /**
     * {@inheritdoc}
     */
    public function testConstructor(): void
    {
        $event = new AfterRequestHandlingEvent(
            $this->app,
            $request = Request::create('/'),
            $response = Response::create()
        );

        $this->assertSame($this->app, $event->application());
        $this->assertSame($request, $event->httpRequest());
        $this->assertSame($response, $event->httpResponse());
    }
}
