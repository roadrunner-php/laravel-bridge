<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel\Tests\Unit;

use Spiral\RoadRunner\Http\PSR7Worker;
use Spiral\RoadRunnerLaravel\Dumper\Dumper;

class HelpersTest extends \Spiral\RoadRunnerLaravel\Tests\AbstractTestCase
{
    /**
     * @covers ::\rr\dump
     */
    public function testRrDump(): void
    {
        $this->mock(Dumper::class)
            ->shouldReceive('dump')
            ->withArgs(['foo', 'bar'])
            ->andReturnUndefined();

        \rr\dump('foo', 'bar');

        $this->assertTrue(true);
    }

    /**
     * @covers ::\rr\dd
     */
    public function testRrDd(): void
    {
        $this->mock(Dumper::class)
            ->shouldReceive('dd')
            ->withArgs(['foo', 123])
            ->andReturnUndefined();

        \rr\dd('foo', 123);

        $this->assertTrue(true);
    }

    /**
     * @covers ::\rr\worker
     */
    public function testRrWorker(): void
    {
        $this->mock(PSR7Worker::class);

        $this->assertInstanceOf(PSR7Worker::class, \rr\worker());
    }
}
