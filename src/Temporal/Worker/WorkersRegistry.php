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

    /**
     * @param WorkerFactoryInterface|null $configuredWorkerFactory Without it, workers are created
     *        without the configured interceptors. Omitting it is deprecated.
     */
    public function __construct(
        private readonly TemporalWorkerFactory $workerFactory,
        private readonly TemporalConfig $config,
        private readonly ?WorkerFactoryInterface $configuredWorkerFactory = null,
    ) {
        $configuredWorkerFactory === null and \trigger_error(
            \sprintf(
                'Creating %s without a %s is deprecated: such workers ignore the configured interceptors.',
                self::class,
                WorkerFactoryInterface::class,
            ),
            \E_USER_DEPRECATED,
        );
    }

    #[\Override]
    public function register(string $name, ?WorkerOptions $options): void
    {
        \assert($name !== '');

        if ($this->has($name)) {
            throw new WorkersRegistryException(
                \sprintf('Temporal worker with given name `%s` has already been registered.', $name),
            );
        }

        if ($this->configuredWorkerFactory instanceof WorkerFactoryWithOptionsInterface) {
            $this->workers[$name] = $this->configuredWorkerFactory->createWithOptions($name, $options);
            return;
        }

        // A deprecation rather than a warning: Laravel turns warnings into exceptions.
        $this->configuredWorkerFactory === null or \trigger_error(
            \sprintf(
                'Temporal worker `%s` is registered without the configured interceptors because %s does not implement %s. Implement it to keep them.',
                $name,
                $this->configuredWorkerFactory::class,
                WorkerFactoryWithOptionsInterface::class,
            ),
            \E_USER_DEPRECATED,
        );

        $this->workers[$name] = $this->workerFactory->newWorker($name, $options);
    }

    #[\Override]
    public function get(string $name): WorkerInterface
    {
        \assert($name !== '');

        if ($this->has($name)) {
            return $this->workers[$name];
        }

        if ($this->configuredWorkerFactory !== null) {
            return $this->workers[$name] = $this->configuredWorkerFactory->create($name);
        }

        $worker = $this->config->getWorkers()[$name] ?? null;
        $this->register($name, $worker instanceof WorkerOptions ? $worker : ($worker['options'] ?? null));

        return $this->workers[$name];
    }

    #[\Override]
    public function has(string $name): bool
    {
        \assert($name !== '');

        return isset($this->workers[$name]);
    }
}
