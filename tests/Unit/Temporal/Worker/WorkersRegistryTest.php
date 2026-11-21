<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel\Tests\Unit\Temporal\Worker;

use Spiral\Core\FactoryInterface;
use Spiral\RoadRunnerLaravel\Temporal\Config\TemporalConfig;
use Spiral\RoadRunnerLaravel\Temporal\Exception\WorkersRegistryException;
use Spiral\RoadRunnerLaravel\Temporal\Worker\WorkerFactory;
use Spiral\RoadRunnerLaravel\Temporal\Worker\WorkerFactoryInterface;
use Spiral\RoadRunnerLaravel\Temporal\Worker\WorkerFactoryWithOptionsInterface;
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

        $registry = self::legacyRegistry($factory, new TemporalConfig());

        Assert::false($registry->has('queue'));
        $registry->register('queue', $options);
        Assert::true($registry->has('queue'));
        Assert::same($registry->get('queue'), $worker);
    }

    public function test_registering_a_name_twice_throws(): never
    {
        $factory = \Mockery::mock(TemporalWorkerFactory::class);
        $factory->shouldReceive('newWorker')->once()->andReturn(\Mockery::mock(WorkerInterface::class));

        $registry = self::legacyRegistry($factory, new TemporalConfig());
        $registry->register('queue', null);

        Expect::exception(WorkersRegistryException::class)
            ->withMessage('Temporal worker with given name `queue` has already been registered.');

        $registry->register('queue', null);
    }

    public function test_two_argument_constructor_is_deprecated(): void
    {
        $deprecations = [];
        \set_error_handler(static function (int $level) use (&$deprecations): bool {
            $deprecations[] = $level;
            return true;
        });

        try {
            new WorkersRegistry(\Mockery::mock(TemporalWorkerFactory::class), new TemporalConfig());
        } finally {
            \restore_error_handler();
        }

        Assert::same($deprecations, [\E_USER_DEPRECATED]);
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

    public function test_get_delegates_to_a_custom_worker_factory(): void
    {
        $worker = \Mockery::mock(WorkerInterface::class);
        $temporal = \Mockery::mock(TemporalWorkerFactory::class);
        $temporal->shouldNotReceive('newWorker');
        $factory = \Mockery::mock(WorkerFactoryInterface::class);
        $factory->shouldReceive('create')->once()->with('queue')->andReturn($worker);

        $registry = new WorkersRegistry($temporal, new TemporalConfig(), $factory);

        Assert::same($registry->get('queue'), $worker);
        Assert::same($registry->get('queue'), $worker);
    }

    public function test_register_passes_explicit_options_to_a_custom_worker_factory(): void
    {
        $options = WorkerOptions::new();
        $worker = \Mockery::mock(WorkerInterface::class);
        $temporal = \Mockery::mock(TemporalWorkerFactory::class);
        $temporal->shouldNotReceive('newWorker');
        $factory = \Mockery::mock(WorkerFactoryWithOptionsInterface::class);
        $factory->shouldReceive('createWithOptions')->once()->with('queue', $options)->andReturn($worker);

        $registry = new WorkersRegistry($temporal, new TemporalConfig(), $factory);
        $registry->register('queue', $options);

        Assert::same($registry->get('queue'), $worker);
    }

    public function test_register_with_a_factory_ignoring_options_falls_back_to_sdk_and_is_deprecated(): void
    {
        $options = WorkerOptions::new();
        $worker = \Mockery::mock(WorkerInterface::class);
        $temporal = \Mockery::mock(TemporalWorkerFactory::class);
        $temporal->shouldReceive('newWorker')->once()->with('queue', $options)->andReturn($worker);
        $factory = \Mockery::mock(WorkerFactoryInterface::class);
        $factory->shouldNotReceive('create');
        $registry = new WorkersRegistry($temporal, new TemporalConfig(), $factory);

        $deprecations = [];
        \set_error_handler(static function (int $level) use (&$deprecations): bool {
            $deprecations[] = $level;
            return true;
        });

        try {
            $registry->register('queue', $options);
        } finally {
            \restore_error_handler();
        }

        Assert::same($deprecations, [\E_USER_DEPRECATED]);
        Assert::same($registry->get('queue'), $worker);
    }

    public function test_get_creates_a_missing_worker_once(): void
    {
        $worker = \Mockery::mock(WorkerInterface::class);
        $factory = \Mockery::mock(TemporalWorkerFactory::class);
        $factory->shouldReceive('newWorker')->once()->with('queue', null)->andReturn($worker);

        $registry = self::legacyRegistry($factory, new TemporalConfig());

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

        $registry = self::legacyRegistry($temporal, $config);

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

    private static function legacyRegistry(TemporalWorkerFactory $factory, TemporalConfig $config): WorkersRegistry
    {
        \set_error_handler(static fn(): bool => true, \E_USER_DEPRECATED);

        try {
            return new WorkersRegistry($factory, $config);
        } finally {
            \restore_error_handler();
        }
    }
}
