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
 * Supported options:
 *
 *     client: non-empty-string,
 *     clients: array<non-empty-string, ClientConfig>,
 *     defaultWorker: non-empty-string,
 *     workers: array<non-empty-string, WorkerOptions|TWorker>,
 *     declarations?: class-string[],
 *     interceptors?: TInterceptor[],
 *     temporalNamespace?: non-empty-string (deprecated),
 *     address?: non-empty-string (deprecated),
 *     clientOptions?: ClientOptions (deprecated)
 *
 * @psalm-type TInterceptor = Interceptor|class-string<Interceptor>|Autowire<Interceptor>
 * @psalm-type TExceptionInterceptor = ExceptionInterceptorInterface|class-string<ExceptionInterceptorInterface>|non-empty-string
 * @psalm-type TWorker = array{
 *     options?: WorkerOptions,
 *     exception_interceptor?: TExceptionInterceptor
 * }
 */
final class TemporalConfig
{
    /** @var array<array-key, mixed> */
    protected array $config = [
        'client' => 'default',
        'clients' => [],
        'defaultWorker' => WorkerFactoryInterface::DEFAULT_TASK_QUEUE,
        'workers' => [],
        'interceptors' => [],
    ];

    /**
     * @param array<array-key, mixed> $config
     */
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
            $namespace = self::nonEmptyString($config['temporalNamespace'] ?? 'default', 'temporalNamespace');
            $clientOptions = $config['clientOptions'] ?? new ClientOptions();
            $clientOptions instanceof ClientOptions or throw new \InvalidArgumentException(
                \sprintf('Temporal option `clientOptions` must be an instance of %s.', ClientOptions::class),
            );

            $clients = $config['clients'] ?? [];
            $clients = \is_array($clients) ? $clients : [];
            $clients['default'] = new ClientConfig(
                new ConnectionConfig(address: self::nonEmptyString($address, 'address')),
                $clientOptions->withNamespace($namespace),
            );

            $config['client'] = 'default';
            $config['clients'] = $clients;
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
        $client = $this->findClientConfig($this->getDefaultClient());
        $namespace = match (true) {
            $client !== null => $client->options->namespace,
            isset($this->config['temporalNamespace']) => $this->config['temporalNamespace'],
            default => 'default',
        };

        return self::nonEmptyString($namespace, 'temporalNamespace');
    }

    public function getDefaultClient(): string
    {
        return self::nonEmptyString($this->config['client'] ?? 'default', 'client');
    }

    public function getClientConfig(string $name): ClientConfig
    {
        return $this->findClientConfig($name) ?? throw new \InvalidArgumentException(
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
        return self::nonEmptyString($this->config['defaultWorker'] ?? null, 'defaultWorker');
    }

    /**
     * @return array<non-empty-string, WorkerOptions|TWorker>
     */
    public function getWorkers(): array
    {
        $workers = [];
        foreach ((array) ($this->config['workers'] ?? []) as $name => $worker) {
            $name = self::nonEmptyString($name, 'workers');
            $workers[$name] = $worker instanceof WorkerOptions ? $worker : self::workerConfig($name, $worker);
        }

        return $workers;
    }

    /**
     * @return array<class-string>
     */
    public function getDeclarations(): array
    {
        return (array) ($this->config['declarations'] ?? []);
    }

    /**
     * @return array<mixed>
     */
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
        $client = $this->findClientConfig($this->getDefaultClient());
        if ($client !== null) {
            return $client->options;
        }

        $options = $this->config['clientOptions'] ?? null;

        return $options instanceof ClientOptions
            ? $options
            : (new ClientOptions())->withNamespace($this->getTemporalNamespace());
    }

    /**
     * @return TWorker
     */
    private static function workerConfig(string $name, mixed $worker): array
    {
        \is_array($worker) or throw new \InvalidArgumentException(\sprintf(
            'Temporal worker `%s` must be configured with %s or an array.',
            $name,
            WorkerOptions::class,
        ));

        $result = [];

        if (isset($worker['options'])) {
            $options = $worker['options'];
            $options instanceof WorkerOptions or throw new \InvalidArgumentException(\sprintf(
                'Option `options` of Temporal worker `%s` must be an instance of %s.',
                $name,
                WorkerOptions::class,
            ));
            $result['options'] = $options;
        }

        if (isset($worker['exception_interceptor'])) {
            $interceptor = $worker['exception_interceptor'];
            if (!$interceptor instanceof ExceptionInterceptorInterface
                && !(\is_string($interceptor) && $interceptor !== '')
            ) {
                throw new \InvalidArgumentException(\sprintf(
                    'Option `exception_interceptor` of Temporal worker `%s` must be a class name, a container alias or an instance of %s.',
                    $name,
                    ExceptionInterceptorInterface::class,
                ));
            }

            $result['exception_interceptor'] = $interceptor;
        }

        return $result;
    }

    /**
     * @return non-empty-string
     */
    private static function nonEmptyString(mixed $value, string $option): string
    {
        \is_string($value) && $value !== '' or throw new \InvalidArgumentException(
            "Temporal option `{$option}` must be a non-empty string.",
        );

        return $value;
    }

    private function findClientConfig(string $name): ?ClientConfig
    {
        $client = ((array) ($this->config['clients'] ?? []))[$name] ?? null;

        return $client instanceof ClientConfig ? $client : null;
    }
}
