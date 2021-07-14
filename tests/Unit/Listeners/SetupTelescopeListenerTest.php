<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel\Tests\Unit\Listeners;

use Mockery as m;
use Laravel\Telescope\Telescope;
use Laravel\Telescope\IncomingEntry;
use Spiral\RoadRunnerLaravel\Listeners\SetupTelescopeListener;
use Spiral\RoadRunnerLaravel\Events\Contracts\WithApplication;

/**
 * @covers \Spiral\RoadRunnerLaravel\Listeners\SetupTelescopeListener
 *
 * @group foo
 */
class SetupTelescopeListenerTest extends AbstractListenerTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->app->register(\Laravel\Telescope\TelescopeServiceProvider::class);
    }

    /**
     * {@inheritdoc}
     */
    public function testHandle(): void
    {
        /** @var m\MockInterface|WithApplication $event_mock */
        $event_mock = m::mock(WithApplication::class)
            ->makePartial()
            ->expects('application')
            ->andReturn($this->app)
            ->getMock();

        $this->assertEmpty(Telescope::$entriesQueue);
        Telescope::recordEvent($event = new IncomingEntry(['name' => 'Spiral\\RoadRunnerLaravel\\']));
        Telescope::recordRequest($request = new IncomingEntry(['controller_action' => 'Laravel\\Telescope\\']));
        $this->assertCount(2, Telescope::$entriesQueue);

        Telescope::flushEntries();
        $this->listenerFactory()->handle($event_mock);

        $this->assertEmpty(Telescope::$entriesQueue);
        Telescope::recordEvent($event);
        Telescope::recordRequest($request);
        $this->assertEmpty(Telescope::$entriesQueue);
    }

    /**
     * @return SetupTelescopeListener
     */
    protected function listenerFactory(): SetupTelescopeListener
    {
        return new SetupTelescopeListener();
    }
}
