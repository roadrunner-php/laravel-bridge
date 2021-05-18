<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel\Tests\Unit\Listeners;

use Mockery as m;
use Illuminate\Notifications\ChannelManager;
use Spiral\RoadRunnerLaravel\Events\Contracts\WithApplication;
use Spiral\RoadRunnerLaravel\Listeners\RebindNotificationChannelManagerListener;

/**
 * @covers \Spiral\RoadRunnerLaravel\Listeners\RebindNotificationChannelManagerListener<extended>
 */
class RebindNotificationChannelManagerListenerTest extends AbstractListenerTestCase
{
    /**
     * {@inheritdoc}
     */
    public function testHandle(): void
    {
        $app_clone = clone $this->app;

        /** @var ChannelManager $channel_manager */
        $channel_manager = $this->app->make(ChannelManager::class);

        $this->setProperty($channel_manager, $container_prop = 'container', $app_clone);

        // burn 'drivers' property
        $channel_manager->driver($channel_manager->getDefaultDriver());

        /** @var m\MockInterface|WithApplication $event_mock */
        $event_mock = m::mock(WithApplication::class)
            ->makePartial()
            ->expects('application')
            ->andReturn($this->app)
            ->getMock();

        $this->assertNotEmpty($this->getProperty($channel_manager, $drivers_prop = 'drivers'));

        $this->listenerFactory()->handle($event_mock);

        $this->assertSame($this->app, $this->getProperty($channel_manager, $container_prop));
        $this->assertEmpty($this->getProperty($channel_manager, $drivers_prop));
    }

    /**
     * @return RebindNotificationChannelManagerListener
     */
    protected function listenerFactory(): RebindNotificationChannelManagerListener
    {
        return new RebindNotificationChannelManagerListener();
    }
}
