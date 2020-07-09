<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel\Tests\Listeners;

use Mockery as m;
use Spiral\RoadRunnerLaravel\Listeners\EnableHttpMethodParameterOverride;
use Symfony\Component\HttpFoundation\Request;
use Spiral\RoadRunnerLaravel\Events\Contracts\WithApplication;
use Spiral\RoadRunnerLaravel\Events\Contracts\WithHttpRequest;

/**
 * @covers \Spiral\RoadRunnerLaravel\Listeners\ForceHttpsListener<extended>
 */
class EnableHttpMethodParameterOverrideTest extends AbstractListenerTestCase
{
    /**
     * @var Request
     */
    protected $request;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->request = $this->app->make('request');
    }

    /**
     * {@inheritdoc}
     */
    public function testHandle(): void
    {
        /** @var m\MockInterface|WithApplication|WithHttpRequest $event */
        $event_mock = m::mock(WithHttpRequest::class)
            ->makePartial()
            ->expects('httpRequest')
            ->andReturn($this->request)
            ->getMock();

        $this->assertFalse($this->request::getHttpMethodParameterOverride());

        $this->listenerFactory()->handle($event_mock);

        $this->assertTrue($this->request::getHttpMethodParameterOverride());
    }

    /**
     * @return EnableHttpMethodParameterOverride
     */
    protected function listenerFactory(): EnableHttpMethodParameterOverride
    {
        return new EnableHttpMethodParameterOverride();
    }
}
