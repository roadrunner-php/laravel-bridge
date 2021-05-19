<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel\Tests\Unit\Dumper\Exceptions;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\VarDumper\Cloner\VarCloner;
use Spiral\RoadRunnerLaravel\Dumper\Stack\FixedArrayStack;
use Spiral\RoadRunnerLaravel\Dumper\Exceptions\DumperException;

/**
 * @covers \Spiral\RoadRunnerLaravel\Dumper\Exceptions\DumperException
 */
class DumperExceptionTest extends \Spiral\RoadRunnerLaravel\Tests\AbstractTestCase
{
    public function testDefaultCode(): void
    {
        $this->assertSame(Response::HTTP_INTERNAL_SERVER_ERROR, (new DumperException())->getCode());
    }

    public function testReport(): void
    {
        (new DumperException())->report();

        $this->markTestSkipped('nothing to test');
    }

    public function testRender(): void
    {
        $cloner = new VarCloner();

        $stack = new FixedArrayStack();
        $stack->push($cloner->cloneVar('foo'));
        $stack->push($cloner->cloneVar(123));

        $e = DumperException::withStack($stack);

        $response = $e->render();

        $this->assertStringContainsString('<html', $response->getContent());
        $this->assertStringContainsString('<body', $response->getContent());
        $this->assertStringContainsString('foo', $response->getContent());
        $this->assertStringContainsString('123', $response->getContent());
        $this->assertSame($e->getCode(), $response->getStatusCode());
    }
}
