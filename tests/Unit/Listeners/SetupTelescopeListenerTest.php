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

        Telescope::recordBatch($any_another_entry = new IncomingEntry([]));
        Telescope::recordCache($any_another_entry);
        Telescope::recordCommand($any_another_entry);
        Telescope::recordDump($any_another_entry);
        Telescope::recordEvent($any_another_entry);
        Telescope::recordException($any_another_entry);
        Telescope::recordGate($any_another_entry);
        Telescope::recordJob($any_another_entry);
        Telescope::recordLog($any_another_entry);
        Telescope::recordMail($any_another_entry);
        Telescope::recordNotification($any_another_entry);
        Telescope::recordQuery($any_another_entry);
        Telescope::recordModelEvent($any_another_entry);
        Telescope::recordRedis($any_another_entry);
        Telescope::recordRequest($any_another_entry);
        Telescope::recordScheduledCommand($any_another_entry);
        Telescope::recordView($any_another_entry);
        Telescope::recordClientRequest($any_another_entry);
        $this->assertCount(18, Telescope::$entriesQueue);
    }

    /**
     * @return SetupTelescopeListener
     */
    protected function listenerFactory(): SetupTelescopeListener
    {
        return new SetupTelescopeListener();
    }

    /**
     * {@inheritDoc}
     */
    protected function tearDown(): void
    {
        Telescope::flushEntries();

        parent::tearDown();
    }
}
