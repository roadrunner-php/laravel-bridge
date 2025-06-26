<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel;

use Spiral\Attributes\AttributeReader;
use Spiral\Attributes\ReaderInterface;
use Spiral\Core\FactoryInterface;
use Spiral\Grpc\Client\Config\ConnectionConfig;
use Spiral\Grpc\Client\Config\GrpcClientConfig;
use Spiral\Grpc\Client\Config\ServiceConfig;
use Spiral\Grpc\Client\Config\TlsConfig;
use Spiral\Grpc\Client\ServiceClientProvider;

final class ServiceProvider extends \Illuminate\Support\ServiceProvider
{
    public static function getConfigRootKey(): string
    {
        return \basename(self::getConfigPath(), '.php');
    }

    public static function getConfigPath(): string
    {
        return __DIR__ . '/../config/roadrunner.php';
    }

    public function register(): void
    {
        $this->app->singleton(ReaderInterface::class, AttributeReader::class);
        $this->initializeConfigs();
        $this->initializeGrpcClientServices();
    }

    protected function initializeConfigs(): void
    {
        $this->mergeConfigFrom(self::getConfigPath(), self::getConfigRootKey());

        $this->publishes([
            \realpath(self::getConfigPath()) => config_path(\basename(self::getConfigPath())),
        ], 'config');
    }

    protected function initializeGrpcClientServices(): void
    {
        $this->app->singleton(FactoryInterface::class, fn() => new class implements FactoryInterface {
            /**
             * @param  class-string  $class
             * @param  array<int, mixed>  $parameters
             */
            public function make(string $class, array $parameters = []): object
            {
                return new $class(...array_values($parameters));
            }
        });
        $this->app->singleton(ServiceClientProvider::class, function () {
            $toNonEmptyStringOrNull = static fn($value): ?string => (is_string($value) && $value !== '') ? $value : null;
            /**
             * @var array<int, array{
             *     connection: non-empty-string,
             *     interfaces: list<class-string>,
             *     tls?: array{
             *         rootCerts?: non-empty-string|null,
             *         privateKey?: non-empty-string|null,
             *         certChain?: non-empty-string|null,
             *         serverName?: non-empty-string|null
             *     }
             * }>
             */
            $rawServices = config('roadrunner.grpc.clients.services', []);
            $services = collect($rawServices);
            $serviceConfigs = [];
            foreach ($services as $service) {
                $tls = null;
                if (isset($service['tls'])) {
                    $tlsConfig = $service['tls'];
                    $tls = new TlsConfig(
                        $toNonEmptyStringOrNull($tlsConfig['rootCerts'] ?? null),
                        $toNonEmptyStringOrNull($tlsConfig['privateKey'] ?? null),
                        $toNonEmptyStringOrNull($tlsConfig['certChain'] ?? null),
                        $toNonEmptyStringOrNull($tlsConfig['serverName'] ?? null),
                    );
                }
                /** @var non-empty-string $connection */
                $connection = $service['connection'];
                /** @var list<class-string> $interfaces */
                $interfaces = $service['interfaces'];
                $serviceConfigs[] = new ServiceConfig(
                    connections: new ConnectionConfig($connection, $tls),
                    interfaces: $interfaces,
                );
            }

            /** @var array<class-string<\Spiral\Interceptors\InterceptorInterface>|\Spiral\Core\Container\Autowire<\Spiral\Interceptors\InterceptorInterface>|\Spiral\Interceptors\InterceptorInterface> $interceptors */
            $interceptors = config('roadrunner.grpc.clients.interceptors', []);
            $config = new GrpcClientConfig(
                interceptors: $interceptors,
                services: $serviceConfigs,
            );

            return new ServiceClientProvider($config, $this->app->make(FactoryInterface::class));
        });

    }
}
