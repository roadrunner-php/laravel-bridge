<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel\Tests\Unit\Listeners;

use Mockery as m;
use RuntimeException;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Spiral\RoadRunnerLaravel\Listeners\RebindRouterListener;
use Spiral\RoadRunnerLaravel\Events\Contracts\WithApplication;
use Spiral\RoadRunnerLaravel\Events\Contracts\WithHttpRequest;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

/**
 * @covers \Spiral\RoadRunnerLaravel\Listeners\RebindRouterListener<extended>
 */
class RebindRouterListenerTest extends AbstractListenerTestCase
{
    /**
     * {@inheritdoc}
     */
    public function testHandle(): void
    {
        $app_clone = clone $this->app;
        /** @var Request $request */
        $request = $this->app->make('request');
        /** @var Router $router */
        $router = $this->app->make('router');

        $this->setProperty($router, $container_prop = 'container', $app_clone);
        $this->setProperty($router->getRoutes()->match($request), $container_prop, $app_clone);

        $this->assertSame($app_clone, $this->getProperty($router, $container_prop));
        $this->assertSame($app_clone, $this->getProperty($router->getRoutes()->match($request), $container_prop));

        /** @var m\MockInterface|WithApplication|WithHttpRequest $event_mock */
        $event_mock = m::mock(\implode(',', [WithApplication::class, WithHttpRequest::class]))
            ->makePartial()
            ->expects('application')
            ->andReturn($this->app)
            ->getMock()
            ->expects('httpRequest')
            ->andReturn($request)
            ->getMock();

        $this->listenerFactory()->handle($event_mock);

        $this->assertSame($this->app, $this->getProperty($router, $container_prop));
        $this->assertSame($this->app, $this->getProperty($router->getRoutes()->match($request), $container_prop));
    }

    /**
     * {@inheritdoc}
     */
    public function testHandleWithSomeAnotherHttpException(): void
    {
        /** @var Request $request */
        $request = $this->app->make('request');
        /** @var m\MockInterface|Router $router */
        $router = m::mock($this->app->make('router'))
            ->makePartial()
            ->expects('getRoutes')
            ->andThrow(new MethodNotAllowedHttpException(['UPDATE']))
            ->getMock();

        $this->app->instance('router', $router);

        /** @var m\MockInterface|WithApplication|WithHttpRequest $event_mock */
        $event_mock = m::mock(\implode(',', [WithApplication::class, WithHttpRequest::class]))
            ->makePartial()
            ->expects('application')
            ->andReturn($this->app)
            ->getMock()
            ->expects('httpRequest')
            ->andReturn($request)
            ->getMock();

        $this->listenerFactory()->handle($event_mock);
    }

    /**
     * {@inheritdoc}
     */
    public function testHandlePassthruNonHttpException(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage($exception_message = 'passthru ' . Str::random());

        /** @var Request $request */
        $request = $this->app->make('request');
        /** @var m\MockInterface|Router $router */
        $router = m::mock($this->app->make('router'))
            ->makePartial()
            ->expects('getRoutes')
            ->andThrow(new RuntimeException($exception_message))
            ->getMock();

        $this->app->instance('router', $router);

        /** @var m\MockInterface|WithApplication|WithHttpRequest $event_mock */
        $event_mock = m::mock(\implode(',', [WithApplication::class, WithHttpRequest::class]))
            ->makePartial()
            ->expects('application')
            ->andReturn($this->app)
            ->getMock()
            ->expects('httpRequest')
            ->andReturn($request)
            ->getMock();

        $this->listenerFactory()->handle($event_mock);
    }

    /**
     * @return RebindRouterListener
     */
    protected function listenerFactory(): RebindRouterListener
    {
        return new RebindRouterListener();
    }
}
