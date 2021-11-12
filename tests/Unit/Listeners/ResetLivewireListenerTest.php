<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel\Tests\Unit\Listeners;

use Mockery as m;
use Livewire\LivewireManager;
use Spiral\RoadRunnerLaravel\Listeners\ResetLivewireListener;
use Spiral\RoadRunnerLaravel\Events\Contracts\WithApplication;

/**
 * @covers \Spiral\RoadRunnerLaravel\Listeners\ResetLivewireListener
 */
class ResetLivewireListenerTest extends AbstractListenerTestCase
{
    /**
     * {@inheritdoc}
     */
    public function testHandle(): void
    {
        /** @var LivewireManager $manager */
        $manager = $this->app->make($manager_abstract = LivewireManager::class);

        $manager_mock = m::mock($manager)
            ->makePartial()
            ->expects('flushState')
            ->withNoArgs()
            ->getMock();

        $this->app->instance($manager_abstract, $manager_mock);

        /** @var m\MockInterface|WithApplication $event_mock */
        $event_mock = m::mock(WithApplication::class)
            ->makePartial()
            ->expects('application')
            ->andReturn($this->app)
            ->getMock();

        $this->listenerFactory()->handle($event_mock);
    }

    /**
     * @return ResetLivewireListener
     */
    protected function listenerFactory(): ResetLivewireListener
    {
        return new ResetLivewireListener();
    }
}
