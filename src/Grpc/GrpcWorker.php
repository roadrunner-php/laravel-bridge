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

        $worker->boot();
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
        /** @var array<class-string> $interceptors for all services */
        $interceptors = $app->get('config')->get('roadrunner.grpc.interceptors', []);

        foreach ($services as $interface => $service) {
            if (is_array($service)) {
                if (!isset($service[0]) || !is_string($service[0])) {
                    throw new \InvalidArgumentException("Service array must have a class name at index 0 for interface: {$interface}");
                }

                $serviceInterceptors = array_merge($interceptors, $service['interceptors'] ?? []);
                $service = $service[0];
            } else {
                $serviceInterceptors = $interceptors;
            }

            $server->registerService($interface, $app->make($service), $serviceInterceptors);
        }

        $server->serve(Worker::create());
    }
}
