<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel\Temporal\Worker;

use Spiral\RoadRunnerLaravel\Temporal\Config\TemporalConfig;
use Spiral\RoadRunnerLaravel\Temporal\Exception\WorkersRegistryException;
use Temporal\Worker\WorkerFactoryInterface as TemporalWorkerFactory;
use Temporal\Worker\WorkerInterface;
use Temporal\Worker\WorkerOptions;

final class WorkersRegistry implements WorkersRegistryInterface
{
    /** @psalm-var array<non-empty-string, WorkerInterface> */
    private array $workers = [];

    public function __construct(
        private readonly TemporalWorkerFactory $workerFactory,
        private readonly TemporalConfig $config,
        private readonly ?WorkerFactory $configuredWorkerFactory = null,
    ) {}

    #[\Override]
    public function register(string $name, ?WorkerOptions $options): void
    {
        \assert($name !== '');

        if ($this->has($name)) {
            throw new WorkersRegistryException(
                \sprintf('Temporal worker with given name `%s` has already been registered.', $name),
            );
        }

        $this->workers[$name] = $this->configuredWorkerFactory !== null
            ? $this->configuredWorkerFactory->createWithOptions($name, $options)
            : $this->workerFactory->newWorker($name, $options);
    }

    #[\Override]
    public function get(string $name): WorkerInterface
    {
        \assert($name !== '');

        if (!$this->has($name)) {
            $worker = $this->config->getWorkers()[$name] ?? null;
            $this->register($name, $worker instanceof WorkerOptions ? $worker : ($worker['options'] ?? null));
        }

        return $this->workers[$name];
    }

    #[\Override]
    public function has(string $name): bool
    {
        \assert($name !== '');

        return isset($this->workers[$name]);
    }
}
