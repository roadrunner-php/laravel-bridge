<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel\Tests\Unit\Listeners;

use Mockery as m;
use Spiral\RoadRunnerLaravel\Events\Contracts\WithApplication;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Spiral\RoadRunnerLaravel\Listeners\RebindDatabaseSessionHandlerListener;

/**
 * @covers \Spiral\RoadRunnerLaravel\Listeners\RebindDatabaseSessionHandlerListener
 */
class RebindDatabaseSessionHandlerListenerTest extends AbstractListenerTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        /** @var ConfigRepository $config */
        $config = $this->app->make(ConfigRepository::class);

        $config->set('session.driver', 'database'); // required for our listener
    }

    /**
     * {@inheritdoc}
     */
    public function testHandle(): void
    {
        $app_clone = clone $this->app;

        /** @var \Illuminate\Session\SessionManager $session */
        $session = $this->app->make($session_abstract = 'session');
        /** @var \Illuminate\Contracts\Session\Session $driver */
        $driver = $session->driver();
        /** @var \Illuminate\Session\DatabaseSessionHandler $handler */
        $handler = $driver->getHandler();

        $this->setProperty($handler, $container_prop = 'container', $app_clone);
        $this->assertNotSame($this->app, $this->getProperty($handler, $container_prop));

        /** @var m\MockInterface|WithApplication $event_mock */
        $event_mock = m::mock(WithApplication::class)
            ->makePartial()
            ->expects('application')
            ->andReturn($this->app)
            ->getMock();

        $this->listenerFactory()->handle($event_mock);

        $this->assertSame($this->app, $this->getProperty($handler, $container_prop));
    }

    /**
     * @return RebindDatabaseSessionHandlerListener
     */
    protected function listenerFactory(): RebindDatabaseSessionHandlerListener
    {
        return new RebindDatabaseSessionHandlerListener();
    }
}
