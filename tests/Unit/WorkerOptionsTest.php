<?php

namespace Spiral\RoadRunnerLaravel\Tests\Unit;

use Spiral\RoadRunnerLaravel\WorkerOptions;
use Spiral\RoadRunnerLaravel\WorkerOptionsInterface;

/**
 * @covers \Spiral\RoadRunnerLaravel\WorkerOptions<extended>
 */
class WorkerOptionsTest extends \Spiral\RoadRunnerLaravel\Tests\AbstractTestCase
{
    /**
     * @return void
     */
    public function testInstanceOf(): void
    {
        $this->assertInstanceOf(WorkerOptionsInterface::class, new WorkerOptions(""));
    }

    /**
     * @return void
     */
    public function testGetters(): void
    {
        $options = new WorkerOptions("foo", true, "bar");

        $this->assertSame("foo", $options->getAppBasePath());
        $this->assertTrue($options->getRefreshApp());
        $this->assertSame("bar", $options->getRelayDsn());
    }
}
