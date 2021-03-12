<?php

namespace Spiral\RoadRunnerLaravel\Tests\Unit\Console\Commands;

use Mockery as m;
use Spiral\RoadRunnerLaravel\WorkerOptions;
use Spiral\RoadRunnerLaravel\WorkerInterface;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use Spiral\RoadRunnerLaravel\Console\Commands\StartCommand;

/**
 * @covers \Spiral\RoadRunnerLaravel\Console\Commands\StartCommand<extended>
 *
 * @group  foo
 */
class StartCommandTest extends \Spiral\RoadRunnerLaravel\Tests\AbstractTestCase
{
    /**
     * @return void
     */
    public function testCommandName(): void
    {
        $this->assertSame('start', (new StartCommand(
            m::mock(WorkerInterface::class)
        ))->getName());
    }

    /**
     * @return void
     */
    public function testCommandOptions(): void
    {
        $definitions = (new StartCommand(
            m::mock(WorkerInterface::class)
        ))->getDefinition();

        $option_laravel_path = $definitions->getOption('laravel-path');
        $this->assertNull($option_laravel_path->getShortcut());
        $this->assertTrue($option_laravel_path->isValueOptional());

        $option_relay_dsn = $definitions->getOption('relay-dsn');
        $this->assertNull($option_relay_dsn->getShortcut());
        $this->assertFalse($option_relay_dsn->isValueOptional());

        $option_refresh_app = $definitions->getOption('refresh-app');
        $this->assertNull($option_refresh_app->getShortcut());
        $this->assertFalse($option_refresh_app->acceptValue());
    }

    /**
     * @return void
     */
    public function testCommandExecuting(): void
    {
        $cmd = new StartCommand(
            m::mock(WorkerInterface::class)
                ->makePartial()
                ->expects("start")
                ->withArgs(function ($options) {
                    $this->assertInstanceOf(WorkerOptions::class, $options);

                    /** @var WorkerOptions $options */
                    $this->assertSame('foo', $options->getAppBasePath());
                    $this->assertSame('bar', $options->getRelayDsn());
                    $this->assertTrue($options->getRefreshApp());

                    return true;
                })
                ->andReturnUndefined()
                ->getMock()
        );

        $cmd->run(new ArrayInput([
            '--laravel-path' => 'foo',
            '--relay-dsn'    => 'bar',
            '--refresh-app'  => null,
        ]), new NullOutput());
    }
}
