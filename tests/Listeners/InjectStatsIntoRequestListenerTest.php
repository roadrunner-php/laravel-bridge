<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel\Tests\Listeners;

use Mockery as m;
use Symfony\Component\HttpFoundation\Request;
use Spiral\RoadRunnerLaravel\Events\Contracts\WithHttpRequest;
use Spiral\RoadRunnerLaravel\Listeners\InjectStatsIntoRequestListener;

/**
 * @covers \Spiral\RoadRunnerLaravel\Listeners\InjectStatsIntoRequestListener<extended>
 */
class InjectStatsIntoRequestListenerTest extends AbstractListenerTestCase
{
    /**
     * @return void
     */
    public function testConstants(): void
    {
        $this->assertSame('getTimestamp', InjectStatsIntoRequestListener::REQUEST_TIMESTAMP_MACRO);
        $this->assertSame('getAllocatedMemory', InjectStatsIntoRequestListener::REQUEST_ALLOCATED_MEMORY_MACRO);
    }

    /**
     * {@inheritdoc}
     */
    public function testHandle(): void
    {
        /** @var Request $original_request */
        $request = $this->app->make('request');

        $current_time     = (float) \microtime(true);
        $allocated_memory = (int) \memory_get_usage();

        /** @var m\MockInterface|WithHttpRequest $event_mock */
        $event_mock = m::mock(WithHttpRequest::class)
            ->makePartial()
            ->expects('httpRequest')
            ->andReturn($request)
            ->getMock();

        $this->assertFalse($request::hasMacro($timestamp_macro = 'getTimestamp'));
        $this->assertFalse($request::hasMacro($mem_alloc_macro = 'getAllocatedMemory'));

        $this->listenerFactory()->handle($event_mock);

        $this->assertTrue($request::hasMacro($timestamp_macro));
        $this->assertGreaterThanOrEqual($current_time, $request::{$timestamp_macro}());
        $this->assertTrue($request::hasMacro($mem_alloc_macro));
        $this->assertGreaterThanOrEqual($allocated_memory, $request::{$mem_alloc_macro}());
    }

    /**
     * @return InjectStatsIntoRequestListener
     */
    protected function listenerFactory(): InjectStatsIntoRequestListener
    {
        return new InjectStatsIntoRequestListener();
    }
}
