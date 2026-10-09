<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel\Temporal\Worker;

use Spiral\Core\FactoryInterface;
use Spiral\RoadRunnerLaravel\Temporal\Config\TemporalConfig;
use Temporal\Exception\ExceptionInterceptorInterface;
use Temporal\Interceptor\PipelineProvider;
use Temporal\Worker\WorkerFactoryInterface as TemporalWorkerFactory;
use Temporal\Worker\WorkerInterface;
use Temporal\Worker\WorkerOptions;

/**
 * @psalm-import-type TWorker from TemporalConfig
 */
final class WorkerFactory implements WorkerFactoryInterface
{
    /** @var array<non-empty-string, WorkerOptions|TWorker> */
    private array $workers = [];

    public function __construct(
        private readonly TemporalWorkerFactory $workerFactory,
        private readonly FactoryInterface $factory,
        private readonly PipelineProvider $pipelineProvider,
        private readonly TemporalConfig $config,
    ) {
        $this->workers = $this->config->getWorkers();
    }

    /**
     * @param non-empty-string $name
     */
    #[\Override]
    public function create(string $name): WorkerInterface
    {
        /** @psalm-suppress TooManyArguments */
        return $this->workerFactory->newWorker(
            $name,
            $this->getWorkerOptions($name),
            $this->getExceptionInterceptor($name),
            $this->pipelineProvider,
        );
    }

    /**
     * @param non-empty-string $name
     */
    private function getWorkerOptions(string $name): ?WorkerOptions
    {
        $worker = $this->workers[$name] ?? null;

        return match (true) {
            $worker instanceof WorkerOptions => $worker,
            isset($worker['options']) => $worker['options'],
            default => null,
        };
    }

    /**
     * @param non-empty-string $name
     */
    private function getExceptionInterceptor(string $name): ?ExceptionInterceptorInterface
    {
        $worker = $this->workers[$name] ?? null;
        if (!\is_array($worker) || !isset($worker['exception_interceptor'])) {
            return null;
        }

        $interceptor = $worker['exception_interceptor'];
        if (\is_string($interceptor)) {
            $interceptor = $this->factory->make($interceptor);
        }

        return $interceptor instanceof ExceptionInterceptorInterface
            ? $interceptor
            : throw new \InvalidArgumentException(\sprintf(
                'Exception interceptor of Temporal worker `%s` must implement %s.',
                $name,
                ExceptionInterceptorInterface::class,
            ));
    }
}
