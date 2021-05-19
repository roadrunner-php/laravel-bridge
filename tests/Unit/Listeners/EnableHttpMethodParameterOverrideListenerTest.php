<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel\Tests\Unit\Listeners;

use Mockery as m;
use Spiral\RoadRunnerLaravel\Events\Contracts\WithHttpRequest;
use Spiral\RoadRunnerLaravel\Listeners\EnableHttpMethodParameterOverrideListener;

/**
 * @covers \Spiral\RoadRunnerLaravel\Listeners\EnableHttpMethodParameterOverrideListener
 */
class EnableHttpMethodParameterOverrideListenerTest extends AbstractListenerTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        \Closure::fromCallable(function (): void {
            /** @see \Symfony\Component\HttpFoundation\Request::$httpMethodParameterOverride */
            self::$httpMethodParameterOverride = false;
        })->bindTo($req = new \Symfony\Component\HttpFoundation\Request(), $req)();
    }

    /**
     * {@inheritdoc}
     */
    public function testHandle(): void
    {
        $request = $this->app->make('request');

        /** @var m\MockInterface|WithHttpRequest $event_mock */
        $event_mock = m::mock(WithHttpRequest::class)
            ->makePartial()
            ->expects('httpRequest')
            ->andReturn($request)
            ->getMock();

        $this->assertFalse($request::getHttpMethodParameterOverride());

        $this->listenerFactory()->handle($event_mock);

        $this->assertTrue($request::getHttpMethodParameterOverride());
    }

    /**
     * @return EnableHttpMethodParameterOverrideListener
     */
    protected function listenerFactory(): EnableHttpMethodParameterOverrideListener
    {
        return new EnableHttpMethodParameterOverrideListener();
    }
}
