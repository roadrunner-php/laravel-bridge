<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel\Tests\Unit\Listeners;

use Mockery as m;
use Illuminate\Support\Str;
use Inertia\ResponseFactory as InertiaResponseFactory;
use Spiral\RoadRunnerLaravel\Listeners\ResetInertiaListener;
use Spiral\RoadRunnerLaravel\Events\Contracts\WithApplication;

/**
 * @covers \Spiral\RoadRunnerLaravel\Listeners\ResetInertiaListener<extended>
 */
class ResetInertiaListenerTest extends AbstractListenerTestCase
{
    /**
     * {@inheritdoc}
     */
    public function testHandle(): void
    {
        /** @see \Inertia\ServiceProvider::register() */
        $this->app->singleton(InertiaResponseFactory::class);

        /** @var InertiaResponseFactory $inertia */
        $inertia = $this->app->make(InertiaResponseFactory::class);

        $inertia->share($key = Str::random(), Str::random());

        /** @var m\MockInterface|WithApplication $event_mock */
        $event_mock = m::mock(WithApplication::class)
            ->makePartial()
            ->expects('application')
            ->andReturn($this->app)
            ->getMock();

        $this->assertNotEmpty($inertia->getShared($key));

        $this->listenerFactory()->handle($event_mock);

        $this->assertEmpty($inertia->getShared($key));
    }

    /**
     * @return ResetInertiaListener
     */
    protected function listenerFactory(): ResetInertiaListener
    {
        return new ResetInertiaListener();
    }
}
