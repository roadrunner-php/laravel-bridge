<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel\Tests\Listeners;

use Mockery as m;
use Symfony\Component\HttpFoundation\Request;
use Spiral\RoadRunnerLaravel\Events\Contracts\WithHttpRequest;
use Spiral\RoadRunnerLaravel\Listeners\EnableHttpMethodParameterOverride;

/**
 * @covers \Spiral\RoadRunnerLaravel\Listeners\EnableHttpMethodParameterOverride<extended>
 */
class EnableHttpMethodParameterOverrideTest extends AbstractListenerTestCase
{
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
     * @return EnableHttpMethodParameterOverride
     */
    protected function listenerFactory(): EnableHttpMethodParameterOverride
    {
        return new EnableHttpMethodParameterOverride();
    }
}
