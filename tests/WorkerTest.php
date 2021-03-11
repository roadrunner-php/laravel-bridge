<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel\Tests;

/**
 * Important note: when you catch mockery errors like `Method respond(<MultiArgumentClosure===true>) from ...`
 * probably it means failed asserts, not `respond` method calls count.
 *
 * @covers \Spiral\RoadRunnerLaravel\Worker<extended>
 */
class WorkerTest extends AbstractTestCase
{
    /**
     * @return void
     */
    public function testWIP(): void
    {
        $this->markTestSkipped();
    }
}
