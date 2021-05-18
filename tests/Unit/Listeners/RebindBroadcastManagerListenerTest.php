<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel\Tests\Unit\Listeners;

use Mockery as m;
use Illuminate\Broadcasting\BroadcastManager;
use Spiral\RoadRunnerLaravel\Events\Contracts\WithApplication;
use Spiral\RoadRunnerLaravel\Listeners\RebindBroadcastManagerListener;

/**
 * @covers \Spiral\RoadRunnerLaravel\Listeners\RebindBroadcastManagerListener<extended>
 */
class RebindBroadcastManagerListenerTest extends AbstractListenerTestCase
{
    /**
     * {@inheritdoc}
     */
    public function testHandle(): void
    {
        $app_clone = clone $this->app;

        /** @var BroadcastManager $broadcast_manager */
        $broadcast_manager = $this->app->make(BroadcastManager::class);

        $this->setProperty($broadcast_manager, $app_prop = 'app', $app_clone);

        /** @var m\MockInterface|WithApplication $event_mock */
        $event_mock = m::mock(WithApplication::class)
            ->makePartial()
            ->expects('application')
            ->andReturn($this->app)
            ->getMock();

        $this->listenerFactory()->handle($event_mock);

        $this->assertSame($this->app, $this->getProperty($broadcast_manager, $app_prop));
    }

    /**
     * @return RebindBroadcastManagerListener
     */
    protected function listenerFactory(): RebindBroadcastManagerListener
    {
        return new RebindBroadcastManagerListener();
    }
}
