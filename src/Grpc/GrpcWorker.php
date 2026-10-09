<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel\Grpc;

use Illuminate\Contracts\Config\Repository;
use Laravel\Octane\ApplicationFactory;
use Spiral\Interceptors\InterceptorInterface;
use Spiral\RoadRunner\GRPC\ServiceInterface;
use Spiral\RoadRunnerLaravel\OctaneWorker;
use Spiral\RoadRunnerLaravel\WorkerInterface;
use Spiral\RoadRunnerLaravel\WorkerOptionsInterface;
use Spiral\RoadRunner\Worker;

final class GrpcWorker implements WorkerInterface
{
    #[\Override]
    public function start(WorkerOptionsInterface $options): void
    {
        $worker = new OctaneWorker(
            appFactory: new ApplicationFactory($options->getAppBasePath()),
        );

        $worker->boot();
        $app = $worker->application();

        $server = new Server(
            worker: $worker,
            options: [
                'debug' => $app->hasDebugModeEnabled(),
            ],
            container: $app,
        );

        $config = $app->make(Repository::class);

        $services = $config->get('roadrunner.grpc.services', []);
        \is_array($services) or throw new \InvalidArgumentException(
            'The `roadrunner.grpc.services` option must be an array.',
        );

        $interceptors = self::interceptors($config->get('roadrunner.grpc.interceptors', []));

        foreach ($services as $interface => $service) {
            \is_string($interface) && \is_a($interface, ServiceInterface::class, true) or throw new \InvalidArgumentException(
                "gRPC service key must be an interface extending ServiceInterface, `{$interface}` given.",
            );

            if (is_array($service)) {
                if (!isset($service['service']) || !is_string($service['service'])) {
                    throw new \InvalidArgumentException("Service array must have a class name at index 'service' for interface: {$interface}");
                }

                $serviceInterceptors = array_merge($interceptors, self::interceptors($service['interceptors'] ?? []));
                $service = $service['service'];
            } else {
                $serviceInterceptors = $interceptors;
            }

            \is_string($service) or throw new \InvalidArgumentException(
                "Service for interface `{$interface}` must be a class name.",
            );

            $instance = $app->make($service);
            $instance instanceof $interface or throw new \InvalidArgumentException(
                "Service `{$service}` must implement `{$interface}`.",
            );

            $server->registerService($interface, $instance, $serviceInterceptors);
        }

        $server->serve(Worker::create());
    }

    /**
     * @return list<InterceptorInterface|non-empty-string>
     */
    private static function interceptors(mixed $interceptors): array
    {
        \is_array($interceptors) or throw new \InvalidArgumentException('gRPC interceptors must be listed in an array.');

        $result = [];
        foreach ($interceptors as $interceptor) {
            if ($interceptor instanceof InterceptorInterface || (\is_string($interceptor) && $interceptor !== '')) {
                $result[] = $interceptor;
                continue;
            }

            throw new \InvalidArgumentException(\sprintf(
                'gRPC interceptor must be a class name or an instance of %s, %s given.',
                InterceptorInterface::class,
                \get_debug_type($interceptor),
            ));
        }

        return $result;
    }
}
