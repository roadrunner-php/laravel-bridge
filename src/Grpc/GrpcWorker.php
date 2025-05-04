<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel\Grpc;

use Laravel\Octane\ApplicationFactory;
use Spiral\RoadRunnerLaravel\OctaneWorker;
use Spiral\RoadRunnerLaravel\WorkerInterface;
use Spiral\RoadRunnerLaravel\WorkerOptionsInterface;
use Spiral\RoadRunner\GRPC\Invoker;
use Spiral\RoadRunner\Worker;

final class GrpcWorker implements WorkerInterface
{
    public function start(WorkerOptionsInterface $options): void
    {
        $worker = new OctaneWorker(
            appFactory: new ApplicationFactory($options->getAppBasePath()),
        );

        $app = $worker->application();

        $server = new Server(
            worker: $worker,
            invoker: new Invoker(),
            options: [
                'debug' => $app->hasDebugModeEnabled(),
            ],
        );

        /** @var array<class-string, class-string> $services */
        $services = $app->get('config')->get('roadrunner.grpc.services', []);

        foreach ($services as $interface => $service) {
            $server->registerService($interface, $app->make($service));
        }

        $server->serve(Worker::create());
    }
}
