<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel\Temporal;

use Laravel\Octane\ApplicationFactory;
use Laravel\Octane\CurrentApplication;
use Spiral\RoadRunnerLaravel\Temporal\Declaration\DeclarationDto;
use Spiral\RoadRunnerLaravel\Temporal\Declaration\DeclarationType;
use Spiral\RoadRunnerLaravel\Temporal\Worker\WorkersRegistryInterface;
use Spiral\RoadRunnerLaravel\WorkerInterface;
use Spiral\RoadRunnerLaravel\WorkerOptionsInterface;
use Temporal\Worker\WorkerFactoryInterface;

final readonly class TemporalWorker implements WorkerInterface
{
    public function start(WorkerOptionsInterface $options): void
    {
        $appFactory = new ApplicationFactory($options->getAppBasePath());
        $app = $appFactory->createApplication();

        CurrentApplication::set($app);

        $workerResolver = $app->get(DeclarationWorkerResolver::class);

        // finds all available workflows, activity types and commands in a given directory
        /** @var list<DeclarationDto> $declarations */
        $declarations = $app->get(DeclarationRegistryInterface::class)->getDeclarationList();

        // factory initiates and runs task queue specific activity and workflow workers
        /** @var \Spiral\RoadRunnerLaravel\Temporal\Worker\WorkerFactoryInterface $factory */
        $factory = $app->get(WorkerFactoryInterface::class);
        /** @var WorkersRegistryInterface $registry */
        $registry = $app->get(WorkersRegistryInterface::class);

        $hasDeclarations = false;
        foreach ($declarations as $declaration) {
            // Worker that listens on a task queue and hosts both workflow and activity implementations.
            $taskQueues = $declaration->taskQueue === null
                ? $workerResolver->resolve($declaration->class)
                : [$declaration->taskQueue];

            foreach ($taskQueues as $taskQueue) {
                $worker = $registry->get($taskQueue);

                if ($declaration->type === DeclarationType::Workflow) {
                    // Workflows are stateful. So you need a type to create instances.
                    $worker->registerWorkflowTypes($declaration->class->getName());
                }

                if ($declaration->type === DeclarationType::Activity) {
                    // Workflows are stateful. So you need a type to create instances.
                    $worker->registerActivity(
                        $declaration->class->getName(),
                        static fn() => $app->make($declaration->class->getName()),
                    );
                }

                $hasDeclarations = true;
            }
        }

        if (!$hasDeclarations) {
            $registry->get(WorkerFactoryInterface::DEFAULT_TASK_QUEUE);
        }

        // start primary loop
        $factory->run();
    }
}
