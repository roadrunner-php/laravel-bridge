<?php

declare(strict_types = 1);

namespace Spiral\RoadRunnerLaravel\Tests\Events;

use Zend\Diactoros\ServerRequest;
use Spiral\RoadRunnerLaravel\Events\BeforeLoopIterationEvent;
use Spiral\RoadRunnerLaravel\Events\Contracts\WithApplication;
use Spiral\RoadRunnerLaravel\Events\Contracts\WithServerRequest;

/**
 * @covers \Spiral\RoadRunnerLaravel\Events\BeforeLoopIterationEvent<extended>
 */
class BeforeLoopIterationEventTest extends AbstractEventTestCase
{
    /**
     * @var string[]
     */
    protected $required_interfaces = [
        WithApplication::class,
        WithServerRequest::class,
    ];

    /**
     * @var string
     */
    protected $event_class = BeforeLoopIterationEvent::class;

    /**
     * {@inheritdoc}
     */
    public function testConstructor(): void
    {
        $event = new BeforeLoopIterationEvent(
            $this->app, $request = new ServerRequest
        );

        $this->assertSame($this->app, $event->application());
        $this->assertSame($request, $event->serverRequest());
    }
}
