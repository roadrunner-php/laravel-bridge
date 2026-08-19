<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel\Tests\Unit\Queue;

use PHPUnit\Framework\TestCase;
use RoadRunner\Jobs\DTO\V1\Stat;
use RoadRunner\Jobs\DTO\V1\Stats;
use Spiral\Goridge\RPC\RPCInterface;
use Spiral\RoadRunner\Jobs\Jobs;
use Spiral\RoadRunnerLaravel\Queue\RoadRunnerQueue;

final class RoadRunnerQueueTest extends TestCase
{
    public function test_queue_sizes_are_read_from_pipeline_stats(): void
    {
        $stats = new Stats([
            'stats' => [
                new Stat([
                    'pipeline' => 'default',
                    'active' => 5,
                    'delayed' => 3,
                    'reserved' => 2,
                ]),
                new Stat([
                    'pipeline' => 'secondary',
                    'active' => 7,
                ]),
            ],
        ]);

        $rpc = $this->createMock(RPCInterface::class);
        $rpc->method('withCodec')->willReturnSelf();
        $rpc->expects(self::exactly(5))
            ->method('call')
            ->with('jobs.Stat', self::isInstanceOf(Stats::class), Stats::class)
            ->willReturn($stats);

        $queue = new RoadRunnerQueue(new Jobs($rpc), $rpc);

        self::assertSame(10, $queue->size());
        self::assertSame(5, $queue->pendingSize());
        self::assertSame(3, $queue->delayedSize());
        self::assertSame(2, $queue->reservedSize());
        self::assertSame(7, $queue->pendingSize('secondary'));
        self::assertNull($queue->creationTimeOfOldestPendingJob());
    }
}
