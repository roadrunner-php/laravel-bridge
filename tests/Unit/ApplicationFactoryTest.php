<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel\Tests\Unit;

use Spiral\RoadRunnerLaravel\ApplicationFactory;

/**
 * @covers \Spiral\RoadRunnerLaravel\ApplicationFactory
 */
class ApplicationFactoryTest extends \Spiral\RoadRunnerLaravel\Tests\AbstractTestCase
{
    public function testCreate(): void
    {
        $this->assertInstanceOf(
            \Illuminate\Foundation\Application::class,
            (new ApplicationFactory())->create(__DIR__ . '/../../vendor/laravel/laravel')
        );
    }

    public function testCreationFailingWithWrongPath(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        (new ApplicationFactory())->create('');
    }
}
