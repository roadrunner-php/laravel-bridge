<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel\Tests\Unit\Dumper\Stack;

use Illuminate\Support\Str;
use Spiral\RoadRunnerLaravel\Dumper\Stack\FixedArrayStack;

/**
 * @group dumper
 *
 * @covers \Spiral\RoadRunnerLaravel\Dumper\Stack
 */
class FixedArrayStackTest extends \Spiral\RoadRunnerLaravel\Tests\AbstractTestCase
{
    public function testPopAndPush(): void
    {
        $stack = new FixedArrayStack();

        $this->assertNull($stack->pop());
        $this->assertSame($want = Str::random(), $stack->pop($want));
        $this->assertSame(0, $stack->count());

        $stack->push($first = 123);
        $stack->push($second = ['foo'], $third = new \stdClass());

        $this->assertSame(3, $stack->count());
        $this->assertSame($third, $stack->pop());

        $this->assertSame(2, $stack->count());
        $this->assertSame($second, $stack->pop());

        $this->assertSame(1, $stack->count());
        $this->assertSame($first, $stack->pop());

        $this->assertSame(0, $stack->count());
        $this->assertNull($stack->pop());
        $this->assertSame(0, $stack->count());

        $stack->push($fourth = 'bar');

        $this->assertSame(1, $stack->count());
        $this->assertSame($fourth, $stack->pop());
        $this->assertSame(0, $stack->count());
        $this->assertNull($stack->pop());
    }

    public function testFlush(): void
    {
        $stack = new FixedArrayStack();

        $stack->flush();
        $this->assertNull($stack->pop());
        $this->assertSame(0, $stack->count());

        $stack->push(123);
        $stack->flush();
        $this->assertSame(0, $stack->count());
        $this->assertNull($stack->pop());
    }

    public function testAll(): void
    {
        $stack = new FixedArrayStack();

        $empty_iterator = $stack->all();
        $this->assertNull($empty_iterator->current());
        $empty_iterator->next();
        $this->assertFalse($empty_iterator->valid());

        $stack->push($first = 123);
        $stack->push($second = ['foo']);

        $iterator = $stack->all();

        $this->assertSame($second, $iterator->current());
        $iterator->next();
        $this->assertSame($first, $iterator->current());
        $this->assertTrue($iterator->valid());
        $iterator->next();
        $this->assertFalse($iterator->valid());
    }
}
