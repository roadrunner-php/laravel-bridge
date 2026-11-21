<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel\Tests\Unit\Temporal\Worker;

use Spiral\Core\FactoryInterface;
use Spiral\RoadRunnerLaravel\Temporal\Config\TemporalConfig;
use Spiral\RoadRunnerLaravel\Temporal\Exception\WorkersRegistryException;
use Spiral\RoadRunnerLaravel\Temporal\Worker\WorkerFactory;
use Spiral\RoadRunnerLaravel\Temporal\Worker\WorkersRegistry;
use Temporal\Exception\ExceptionInterceptorInterface;
use Temporal\Interceptor\PipelineProvider;
use Temporal\Worker\WorkerFactoryInterface as TemporalWorkerFactory;
use Temporal\Worker\WorkerInterface;
use Temporal\Worker\WorkerOptions;
use Testo\Assert;
use Testo\Data\DataSet;
use Testo\Expect;
use Testo\Test;

#[Test]
final class WorkersRegistryTest
{
    public function test_register_creates_a_worker_once(): void
    {
        $options = WorkerOptions::new();
        $worker = \Mockery::mock(WorkerInterface::class);
        $factory = \Mockery::mock(TemporalWorkerFactory::class);
        $factory->shouldReceive('newWorker')->once()->with('queue', $options)->andReturn($worker);

        $registry = new WorkersRegistry($factory, new TemporalConfig());

        Assert::false($registry->has('queue'));
        $registry->register('queue', $options);
        Assert::true($registry->has('queue'));
        Assert::same($registry->get('queue'), $worker);
    }

    public function test_registering_a_name_twice_throws(): never
    {
        $factory = \Mockery::mock(TemporalWorkerFactory::class);
        $factory->shouldReceive('newWorker')->once()->andReturn(\Mockery::mock(WorkerInterface::class));

        $registry = new WorkersRegistry($factory, new TemporalConfig());
        $registry->register('queue', null);

        Expect::exception(WorkersRegistryException::class)
            ->withMessage('Temporal worker with given name `queue` has already been registered.');

        $registry->register('queue', null);
    }

    public function test_get_preserves_configured_options_and_interceptors(): void
    {
        $options = WorkerOptions::new();
        $worker = \Mockery::mock(WorkerInterface::class);
        $pipeline = \Mockery::mock(PipelineProvider::class);
        $exceptionInterceptor = \Mockery::mock(ExceptionInterceptorInterface::class);
        $temporal = \Mockery::mock(TemporalWorkerFactory::class);
        $temporal->shouldReceive('newWorker')->once()
            ->with('queue', $options, $exceptionInterceptor, $pipeline)->andReturn($worker);
        $config = new TemporalConfig([
            'workers' => ['queue' => ['options' => $options, 'exception_interceptor' => $exceptionInterceptor]],
        ]);
        $factory = new WorkerFactory($temporal, \Mockery::mock(FactoryInterface::class), $pipeline, $config);
        $registry = new WorkersRegistry($temporal, $config, $factory);

        Assert::same($registry->get('queue'), $worker);
        Assert::same($registry->get('queue'), $worker);
    }

    public function test_get_creates_a_missing_worker_once(): void
    {
        $worker = \Mockery::mock(WorkerInterface::class);
        $factory = \Mockery::mock(TemporalWorkerFactory::class);
        $factory->shouldReceive('newWorker')->once()->with('queue', null)->andReturn($worker);

        $registry = new WorkersRegistry($factory, new TemporalConfig());

        Assert::same($registry->get('queue'), $worker);
        Assert::same($registry->get('queue'), $worker);
    }

    #[DataSet(['direct'], 'direct options')]
    #[DataSet(['nested'], 'nested options')]
    public function test_two_argument_constructor_preserves_configured_options(string $shape): void
    {
        $options = WorkerOptions::new()->withMaxConcurrentActivityExecutionSize(2);
        $worker = \Mockery::mock(WorkerInterface::class);
        $temporal = \Mockery::mock(TemporalWorkerFactory::class);
        $temporal->shouldReceive('newWorker')->once()->with('queue', $options)->andReturn($worker);
        $config = new TemporalConfig(['workers' => ['queue' => $shape === 'direct' ? $options : ['options' => $options]]]);

        $registry = new WorkersRegistry(workerFactory: $temporal, config: $config);

        Assert::same($registry->get('queue'), $worker);
        Assert::same($registry->get('queue'), $worker);
    }

    #[DataSet(['override'], 'explicit options override configuration')]
    #[DataSet(['default'], 'explicit null retains SDK defaults')]
    public function test_register_preserves_explicit_options_and_interceptors(string $mode): void
    {
        $options = $mode === 'override' ? WorkerOptions::new()->withMaxConcurrentActivityExecutionSize(2) : null;
        $pipeline = \Mockery::mock(PipelineProvider::class);
        $interceptor = \Mockery::mock(ExceptionInterceptorInterface::class);
        $worker = \Mockery::mock(WorkerInterface::class);
        $temporal = \Mockery::mock(TemporalWorkerFactory::class);
        $temporal->shouldReceive('newWorker')->once()->with('queue', $options, $interceptor, $pipeline)->andReturn($worker);
        $config = new TemporalConfig([
            'workers' => ['queue' => ['options' => WorkerOptions::new(), 'exception_interceptor' => $interceptor]],
        ]);
        $factory = new WorkerFactory($temporal, \Mockery::mock(FactoryInterface::class), $pipeline, $config);
        $registry = new WorkersRegistry($temporal, $config, $factory);

        $registry->register('queue', $options);

        Assert::same($registry->get('queue'), $worker);
    }
}
