<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel\Tests\Unit\Temporal\Worker;

use Spiral\RoadRunnerLaravel\Temporal\Config\TemporalConfig;
use Spiral\RoadRunnerLaravel\Temporal\Exception\WorkersRegistryException;
use Spiral\RoadRunnerLaravel\Temporal\Worker\WorkersRegistry;
use Temporal\Worker\WorkerFactoryInterface;
use Temporal\Worker\WorkerInterface;
use Temporal\Worker\WorkerOptions;
use Testo\Assert;
use Testo\Expect;
use Testo\Test;

#[Test]
final class WorkersRegistryTest
{
    public function test_register_creates_a_worker_once(): void
    {
        $options = WorkerOptions::new();
        $worker = \Mockery::mock(WorkerInterface::class);
        $factory = \Mockery::mock(WorkerFactoryInterface::class);
        $factory->shouldReceive('newWorker')->once()->with('queue', $options)->andReturn($worker);

        $registry = new WorkersRegistry($factory, new TemporalConfig());

        Assert::false($registry->has('queue'));
        $registry->register('queue', $options);
        Assert::true($registry->has('queue'));
        Assert::same($registry->get('queue'), $worker);
    }

    public function test_registering_a_name_twice_throws(): never
    {
        $factory = \Mockery::mock(WorkerFactoryInterface::class);
        $factory->shouldReceive('newWorker')->andReturn(\Mockery::mock(WorkerInterface::class));

        $registry = new WorkersRegistry($factory, new TemporalConfig());
        $registry->register('queue', null);

        Expect::exception(WorkersRegistryException::class)
            ->withMessage('Temporal worker with given name `queue` has already been registered.');

        $registry->register('queue', null);
    }

    public function test_get_registers_a_missing_worker_with_configured_options(): void
    {
        $options = WorkerOptions::new();
        $worker = \Mockery::mock(WorkerInterface::class);
        $factory = \Mockery::mock(WorkerFactoryInterface::class);
        $factory->shouldReceive('newWorker')->once()->with('queue', $options)->andReturn($worker);

        $registry = new WorkersRegistry($factory, new TemporalConfig(['workers' => ['queue' => $options]]));

        Assert::same($registry->get('queue'), $worker);
        Assert::same($registry->get('queue'), $worker);
    }

    public function test_get_ignores_options_that_are_not_worker_options(): void
    {
        $worker = \Mockery::mock(WorkerInterface::class);
        $factory = \Mockery::mock(WorkerFactoryInterface::class);
        $factory->shouldReceive('newWorker')->once()->with('queue', null)->andReturn($worker);

        $registry = new WorkersRegistry($factory, new TemporalConfig(['workers' => ['queue' => ['options' => null]]]));

        Assert::same($registry->get('queue'), $worker);
    }
}
