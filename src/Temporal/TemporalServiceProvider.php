<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel\Temporal;

use Illuminate\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use Spiral\Attributes\ReaderInterface;
use Spiral\RoadRunnerLaravel\Temporal\Config\TemporalConfig;
use Spiral\RoadRunnerLaravel\Temporal\Worker\WorkerFactory;
use Spiral\RoadRunnerLaravel\Temporal\Worker\WorkerFactoryInterface;
use Spiral\RoadRunnerLaravel\Temporal\Worker\WorkersRegistry;
use Spiral\RoadRunnerLaravel\Temporal\Worker\WorkersRegistryInterface;
use Temporal\Client\GRPC\ServiceClient;
use Temporal\Client\GRPC\ServiceClientInterface;
use Temporal\Client\ScheduleClient;
use Temporal\Client\ScheduleClientInterface;
use Temporal\Client\WorkflowClient;
use Temporal\Client\WorkflowClientInterface;
use Temporal\DataConverter\DataConverter;
use Temporal\DataConverter\DataConverterInterface;
use Temporal\Interceptor\PipelineProvider;
use Temporal\Interceptor\SimplePipelineProvider;
use Temporal\Internal\Interceptor\Interceptor;
use Temporal\Worker\ServiceCredentials;
use Temporal\Worker\Transport\Goridge;
use Temporal\Worker\WorkerFactoryInterface as TemporalWorkerFactoryInterface;

final class TemporalServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(TemporalConfig::class, static fn(Application $app) => new TemporalConfig(
            config: $app['config']->get('roadrunner.temporal', []),
        ));

        $this->app->singleton(WorkersRegistryInterface::class, WorkersRegistry::class);
        $this->app->singleton(WorkerFactoryInterface::class, WorkerFactory::class);
        $this->app->singleton(DeclarationRegistryInterface::class, static function (Application $app) {
            $config = $app->get(TemporalConfig::class);

            $registry = new DeclarationRegistry(
                reader: $app->get(ReaderInterface::class),
            );

            foreach ($config->getDeclarations() as $declaration) {
                $registry->addDeclaration($declaration);
            }

            return $registry;
        });

        $this->app->singleton(TemporalWorkerFactoryInterface::class, static fn(Application $app) => new \Temporal\WorkerFactory(
            dataConverter: $app->get(DataConverterInterface::class),
            rpc: Goridge::create(),
            credentials: $app->get(ServiceCredentials::class),
        ));

        $this->app->singleton(ServiceCredentials::class, static function (Application $app) {
            $config = $app->get(TemporalConfig::class);

            $client = $config->getClientConfig($config->getDefaultClient());
            $result = ServiceCredentials::create();
            // Set the API key if it is provided.
            $token = $client->connection->authToken;
            $token === null or $result = $result->withApiKey((string) $token);

            return $result;
        });

        $this->app->singleton(WorkflowClientInterface::class, static fn(Application $app) => new WorkflowClient(
            serviceClient: $app->get(ServiceClientInterface::class),
            options: $app->get(TemporalConfig::class)->getClientOptions(),
            converter: $app->get(DataConverterInterface::class),
            interceptorProvider: $app->get(PipelineProvider::class),
        ));

        $this->app->singleton(ScheduleClientInterface::class, static fn(Application $app) => new ScheduleClient(
            serviceClient: $app->get(ServiceClientInterface::class),
            options: $app->get(TemporalConfig::class)->getClientOptions(),
            converter: $app->get(DataConverterInterface::class),
        ));

        $this->app->singleton(DataConverterInterface::class, static fn() => DataConverter::createDefault());

        $this->app->singleton(PipelineProvider::class, static function (Application $app) {
            $config = $app->get(TemporalConfig::class);
            /** @var Interceptor[] $interceptors */
            $interceptors = \array_map(
                static fn(mixed $interceptor) => match (true) {
                    \is_string($interceptor) => $app->make($interceptor),
                    default => $interceptor,
                },
                $config->getInterceptors(),
            );

            return new SimplePipelineProvider($interceptors);
        });

        $this->app->singleton(ServiceClientInterface::class, static function (Application $app) {
            $config = $app->get(TemporalConfig::class);
            $client = $config->getClientConfig($config->getDefaultClient());
            $connection = $client->connection;

            $isSecure = $connection->isSecure() || $connection->authToken !== null;

            $result = $isSecure
                ? ServiceClient::createSSL(
                    address: $connection->address,
                    crt: $connection->tls?->rootCerts,
                    clientKey: $connection->tls?->privateKey,
                    clientPem: $connection->tls?->certChain,
                    overrideServerName: $connection->tls?->serverName,
                )
                : ServiceClient::create(address: $connection->address);

            $connection->authToken === null or $result = $result->withAuthKey($connection->authToken);

            return $result->withContext($client->context);
        });
    }
}
