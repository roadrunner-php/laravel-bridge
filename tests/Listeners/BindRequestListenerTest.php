<?php

declare(strict_types = 1);

namespace Spiral\RoadRunnerLaravel\Tests\Listeners;

use Mockery as m;
use Symfony\Component\HttpFoundation\Request;
use Spiral\RoadRunnerLaravel\Listeners\BindRequestListener;
use Spiral\RoadRunnerLaravel\Events\Contracts\WithApplication;
use Spiral\RoadRunnerLaravel\Events\Contracts\WithHttpRequest;

/**
 * @covers \Spiral\RoadRunnerLaravel\Listeners\BindRequestListener<extended>
 */
class BindRequestListenerTest extends AbstractListenerTestCase
{
    /**
     * {@inheritdoc}
     */
    public function testHandle(): void
    {
        /** @var Request $modified_request */
        $modified_request = clone $this->app->make('request');
        /** @var Request $original_request */
        $original_request = $this->app->make('request');

        /** @var m\MockInterface|WithApplication|WithHttpRequest $event_mock */
        $event_mock = m::mock(\implode(',', [WithApplication::class, WithHttpRequest::class]))
            ->makePartial()
            ->expects('application')
            ->andReturn($this->app)
            ->getMock()
            ->expects('httpRequest')
            ->andReturn($modified_request)
            ->getMock();

        $this->listenerFactory()->handle($event_mock);

        $this->assertNotSame($original_request, $this->app->make('request'));
    }

    /**
     * @return BindRequestListener
     */
    protected function listenerFactory(): BindRequestListener
    {
        return new BindRequestListener;
    }
}
