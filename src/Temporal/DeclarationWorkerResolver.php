<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel\Temporal;

use Spiral\Attributes\ReaderInterface;
use Spiral\RoadRunnerLaravel\Temporal\Attribute\AssignWorker;
use Spiral\RoadRunnerLaravel\Temporal\Config\TemporalConfig;

final readonly class DeclarationWorkerResolver
{
    public function __construct(
        private ReaderInterface $reader,
        private TemporalConfig $config,
    ) {}

    /**
     * Find the worker name for the given workflow or class declaration. If no worker is assigned, the default task
     * queue name is returned.
     *
     * @param \ReflectionClass<object> $declaration
     *
     * @return non-empty-list<string>
     */
    public function resolve(\ReflectionClass $declaration): array
    {
        $queue = $this->resolveTaskQueues($declaration);

        if ($queue !== []) {
            return $queue;
        }

        return [
            $this->config->getDefaultWorker(),
        ];
    }

    /**
     * @param \ReflectionClass<object> $declaration
     *
     * @return list<string>
     */
    private function resolveTaskQueues(\ReflectionClass $declaration): array
    {
        $assignWorker = $this->reader->getClassMetadata($declaration, AssignWorker::class);

        $workers = [];

        foreach ($assignWorker as $worker) {
            $workers[] = $worker->taskQueue;
        }

        if ($workers !== []) {
            return $workers;
        }

        $parents = $declaration->getInterfaceNames();
        foreach ($parents as $parent) {
            $queueName = $this->resolveTaskQueues(new \ReflectionClass($parent));
            if ($queueName !== []) {
                return $queueName;
            }
        }

        return [];
    }
}
