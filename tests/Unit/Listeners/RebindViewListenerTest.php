<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel\Tests\Unit\Listeners;

use Mockery as m;
use Spiral\RoadRunnerLaravel\Listeners\RebindViewListener;
use Spiral\RoadRunnerLaravel\Events\Contracts\WithApplication;

/**
 * @covers \Spiral\RoadRunnerLaravel\Listeners\RebindViewListener<extended>
 */
class RebindViewListenerTest extends AbstractListenerTestCase
{
    /**
     * {@inheritdoc}
     */
    public function testHandle(): void
    {
        $app_clone = clone $this->app;
        /** @var \Illuminate\View\Factory $view */
        $view = $this->app->make('view');

        $this->setProperty($view, $container_prop = 'container', $app_clone);
        $this->setProperty($view, $shared_prop = 'shared', ['app' => $app_clone]);

        $this->assertSame($app_clone, $this->getProperty($view, $container_prop));
        $this->assertSame($app_clone, $this->getProperty($view, $shared_prop)['app']);

        /** @var m\MockInterface|WithApplication $event_mock */
        $event_mock = m::mock(WithApplication::class)
            ->makePartial()
            ->expects('application')
            ->andReturn($this->app)
            ->getMock();

        $this->listenerFactory()->handle($event_mock);

        $this->assertSame($this->app, $this->getProperty($view, $container_prop));
        $this->assertSame($this->app, $this->getProperty($view, $shared_prop)['app']);
    }

    /**
     * @return RebindViewListener
     */
    protected function listenerFactory(): RebindViewListener
    {
        return new RebindViewListener();
    }
}
