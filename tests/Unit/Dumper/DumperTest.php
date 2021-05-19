<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel\Tests\Unit\Dumper;

use Spiral\RoadRunnerLaravel\Dumper\Dumper;
use Symfony\Component\VarDumper\Cloner\Data;
use Spiral\RoadRunnerLaravel\Dumper\Stoppers\Noop;
use Spiral\RoadRunnerLaravel\Dumper\Stack\FixedArrayStack;
use Spiral\RoadRunnerLaravel\Dumper\Exceptions\DumperException;

/**
 * @covers \Spiral\RoadRunnerLaravel\Dumper\Dumper
 */
class DumperTest extends \Spiral\RoadRunnerLaravel\Tests\AbstractTestCase
{
    public function testDumpInNonCliMode(): void
    {
        $this->disableCliModeEmulation();

        $dumper = new Dumper($stack = new FixedArrayStack(), new Noop());

        $this->assertSame(0, $stack->count());

        $dumper->dump('foo', 123);

        $this->assertSame(2, $stack->count());

        foreach ($stack->all() as $item) {
            $this->assertInstanceOf(Data::class, $item);
        }
    }

    public function testDdInNonCliMode(): void
    {
        $this->disableCliModeEmulation();

        $dumper = new Dumper($stack = new FixedArrayStack(), new Noop());

        $this->assertSame(0, $stack->count());

        $caught = false;

        try {
            $dumper->dd('foo', 123);
        } catch (DumperException $e) {
            $caught = true;
        } finally {
            $this->assertSame(0, $stack->count());
            $this->assertTrue($caught);
        }
    }

    /**
     * {@inheritDoc}
     */
    protected function tearDown(): void
    {
        $this->enableCliModeEmulation();

        parent::tearDown();
    }
}
