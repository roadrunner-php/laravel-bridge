<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel\Temporal\Config;

use Spiral\Core\Container\Autowire;
use Spiral\RoadRunnerLaravel\Temporal\Interceptor\HandleActivityInterceptor;
use Temporal\Client\ClientOptions;
use Temporal\Exception\ExceptionInterceptorInterface;
use Temporal\Internal\Interceptor\Interceptor;
use Temporal\Worker\WorkerFactoryInterface;
use Temporal\Worker\WorkerOptions;

/**
 * @psalm-type TInterceptor = Interceptor|class-string<Interceptor>|Autowire<Interceptor>
 * @psalm-type TExceptionInterceptor = ExceptionInterceptorInterface|class-string<ExceptionInterceptorInterface>|Autowire<ExceptionInterceptorInterface>
 * @psalm-type TWorker = array{
 *     options?: WorkerOptions,
 *     exception_interceptor?: TExceptionInterceptor
 * }
 *
 * @property array{
 *     client: non-empty-string,
 *     clients: array<non-empty-string, ClientConfig>,
 *     defaultWorker: non-empty-string,
 *     workers: array<non-empty-string, WorkerOptions|TWorker>,
 *     interceptors?: TInterceptor[],
 *     temporalNamespace?: non-empty-string,
 *     address?: non-empty-string,
 *     clientOptions?: ClientOptions
 * } $config
 */
final class TemporalConfig
{
    protected array $config = [
        'client' => 'default',
        'clients' => [],
        'defaultWorker' => WorkerFactoryInterface::DEFAULT_TASK_QUEUE,
        'workers' => [],
        'interceptors' => [],
    ];

    public function __construct(array $config = [])
    {
        // Legacy support. Will be removed in further versions.
        // If you read this, please remove `address` option from your configuration and use `clients` instead.
        $address = $config['address'] ?? null;
        if ($address !== null) {
            \trigger_error(
                'Temporal options `address`, `clientOptions`, `temporalNamespace` are deprecated.',
                \E_USER_DEPRECATED,
            );

            // Create a default client configuration from the legacy options.
            $namespace = $config['temporalNamespace'] ?? 'default';
            $clientOptions = ($config['clientOptions'] ?? new ClientOptions())
                ->withNamespace($namespace);

            $config['client'] = 'default';
            $config['clients']['default'] = new ClientConfig(
                new ConnectionConfig(address: $address),
                $clientOptions,
            );
        }

        $this->config = \array_merge($this->config, $config);
    }

    /**
     * Get default namespace for Temporal client.
     *
     * @return non-empty-string
     *
     * @deprecated
     */
    public function getTemporalNamespace(): string
    {
        $client = $this->getDefaultClient();
        return match (true) {
            isset($this->config['clients'][$client]) => $this->config['clients'][$client]->options->namespace,
            isset($this->config['temporalNamespace']) => $this->config['temporalNamespace'],
            default => 'default',
        };
    }

    public function getDefaultClient(): string
    {
        return $this->config['client'] ?? 'default';
    }

    public function getClientConfig(string $name): ClientConfig
    {
        return $this->config['clients'][$name] ?? throw new \InvalidArgumentException(
            "Temporal client config `{$name}` is not defined.",
        );
    }

    /**
     * Get default connection address.
     *
     * @deprecated
     */
    public function getAddress(): string
    {
        return $this->getClientConfig($this->getDefaultClient())->connection->address;
    }

    /**
     * @return non-empty-string
     */
    public function getDefaultWorker(): string
    {
        return $this->config['defaultWorker'];
    }

    /**
     * @return array<non-empty-string, WorkerOptions|TWorker>
     */
    public function getWorkers(): array
    {
        return (array) ($this->config['workers'] ?? []);
    }

    /**
     * @return array<class-string>
     */
    public function getDeclarations(): array
    {
        return (array) ($this->config['declarations'] ?? []);
    }

    public function getInterceptors(): array
    {
        $interceptors = (array) ($this->config['interceptors'] ?? []);
        $interceptors[] = HandleActivityInterceptor::class;

        return $interceptors;
    }

    /**
     * Get default client options.
     *
     * @deprecated
     */
    public function getClientOptions(): ClientOptions
    {
        $client = $this->getDefaultClient();
        return match (true) {
            isset($this->config['clients'][$client]) => $this->config['clients'][$client]->options,
            isset($this->config['clientOptions']) => $this->config['clientOptions'],
            default => (new ClientOptions())->withNamespace($this->getTemporalNamespace()),
        };
    }
}
