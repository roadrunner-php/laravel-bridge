<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel\Tests\Unit\Temporal\Worker;

use Spiral\Core\FactoryInterface;
use Spiral\RoadRunnerLaravel\Temporal\Config\TemporalConfig;
use Spiral\RoadRunnerLaravel\Temporal\Worker\WorkerFactory;
use Temporal\Exception\ExceptionInterceptorInterface;
use Temporal\Interceptor\PipelineProvider;
use Temporal\Worker\WorkerFactoryInterface;
use Temporal\Worker\WorkerInterface;
use Temporal\Worker\WorkerOptions;
use Testo\Assert;
use Testo\Expect;
use Testo\Test;

#[Test]
final class WorkerFactoryTest
{
    public function test_worker_without_config_gets_no_options(): void
    {
        $pipeline = \Mockery::mock(PipelineProvider::class);
        $worker = \Mockery::mock(WorkerInterface::class);
        $temporal = \Mockery::mock(WorkerFactoryInterface::class);
        $temporal->shouldReceive('newWorker')->once()->with('queue', null, null, $pipeline)->andReturn($worker);

        $factory = new WorkerFactory($temporal, \Mockery::mock(FactoryInterface::class), $pipeline, new TemporalConfig());

        Assert::same($factory->create('queue'), $worker);
    }

    public function test_worker_options_can_be_configured_directly(): void
    {
        $options = WorkerOptions::new();
        $pipeline = \Mockery::mock(PipelineProvider::class);
        $temporal = \Mockery::mock(WorkerFactoryInterface::class);
        $temporal->shouldReceive('newWorker')->once()->with('queue', $options, null, $pipeline)
            ->andReturn(\Mockery::mock(WorkerInterface::class));

        $factory = new WorkerFactory(
            $temporal,
            \Mockery::mock(FactoryInterface::class),
            $pipeline,
            new TemporalConfig(['workers' => ['queue' => $options]]),
        );

        $factory->create('queue');
    }

    public function test_exception_interceptor_class_is_built_by_the_container(): void
    {
        $options = WorkerOptions::new();
        $interceptor = \Mockery::mock(ExceptionInterceptorInterface::class);
        $container = \Mockery::mock(FactoryInterface::class);
        $container->shouldReceive('make')->once()->with('App\\Interceptor')->andReturn($interceptor);

        $pipeline = \Mockery::mock(PipelineProvider::class);
        $temporal = \Mockery::mock(WorkerFactoryInterface::class);
        $temporal->shouldReceive('newWorker')->once()->with('queue', $options, $interceptor, $pipeline)
            ->andReturn(\Mockery::mock(WorkerInterface::class));

        $factory = new WorkerFactory($temporal, $container, $pipeline, new TemporalConfig([
            'workers' => ['queue' => ['options' => $options, 'exception_interceptor' => 'App\\Interceptor']],
        ]));

        $factory->create('queue');
    }

    public function test_exception_interceptor_alias_must_resolve_to_an_interceptor(): never
    {
        $container = \Mockery::mock(FactoryInterface::class);
        $container->shouldReceive('make')->once()->with('interceptor.alias')->andReturn(new \stdClass());

        $factory = new WorkerFactory(
            \Mockery::mock(WorkerFactoryInterface::class),
            $container,
            \Mockery::mock(PipelineProvider::class),
            new TemporalConfig(['workers' => ['queue' => ['exception_interceptor' => 'interceptor.alias']]]),
        );

        Expect::exception(\InvalidArgumentException::class)->withMessageContaining('must implement');

        $factory->create('queue');
    }

    public function test_exception_interceptor_instance_is_used_as_is(): void
    {
        $interceptor = \Mockery::mock(ExceptionInterceptorInterface::class);
        $pipeline = \Mockery::mock(PipelineProvider::class);
        $temporal = \Mockery::mock(WorkerFactoryInterface::class);
        $temporal->shouldReceive('newWorker')->once()->with('queue', null, $interceptor, $pipeline)
            ->andReturn(\Mockery::mock(WorkerInterface::class));

        $factory = new WorkerFactory($temporal, \Mockery::mock(FactoryInterface::class), $pipeline, new TemporalConfig([
            'workers' => ['queue' => ['exception_interceptor' => $interceptor]],
        ]));

        $factory->create('queue');
    }
}
