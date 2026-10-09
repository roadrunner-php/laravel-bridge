<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel\Tests\Unit\Temporal\Config;

use Spiral\RoadRunnerLaravel\Temporal\Config\ClientConfig;
use Spiral\RoadRunnerLaravel\Temporal\Config\ConnectionConfig;
use Spiral\RoadRunnerLaravel\Temporal\Config\TemporalConfig;
use Spiral\RoadRunnerLaravel\Temporal\Interceptor\HandleActivityInterceptor;
use Temporal\Client\ClientOptions;
use Temporal\Exception\ExceptionInterceptorInterface;
use Temporal\Worker\WorkerFactoryInterface;
use Temporal\Worker\WorkerOptions;
use Testo\Assert;
use Testo\Data\DataSet;
use Testo\Expect;
use Testo\Test;

#[Test]
final class TemporalConfigTest
{
    public function test_defaults(): void
    {
        $config = new TemporalConfig();

        Assert::same($config->getDefaultClient(), 'default');
        Assert::same($config->getDefaultWorker(), WorkerFactoryInterface::DEFAULT_TASK_QUEUE);
        Assert::same($config->getWorkers(), []);
        Assert::same($config->getDeclarations(), []);
        Assert::same($config->getInterceptors(), [HandleActivityInterceptor::class]);
        Assert::same($config->getTemporalNamespace(), 'default');
        Assert::same($config->getClientOptions()->namespace, 'default');
    }

    public function test_client_config_is_resolved_by_name(): void
    {
        $client = new ClientConfig(
            new ConnectionConfig('temporal:7233'),
            (new ClientOptions())->withNamespace('billing'),
        );

        $config = new TemporalConfig([
            'client' => 'main',
            'clients' => ['main' => $client],
        ]);

        Assert::same($config->getDefaultClient(), 'main');
        Assert::same($config->getClientConfig('main'), $client);
        Assert::same($config->getAddress(), 'temporal:7233');
        Assert::same($config->getTemporalNamespace(), 'billing');
        Assert::same($config->getClientOptions(), $client->options);
    }

    public function test_unknown_client_config_throws(): never
    {
        Expect::exception(\InvalidArgumentException::class)
            ->withMessage('Temporal client config `missing` is not defined.');

        (new TemporalConfig())->getClientConfig('missing');
    }

    public function test_legacy_address_creates_a_default_client_and_is_deprecated(): void
    {
        $deprecations = [];
        \set_error_handler(static function (int $level, string $message) use (&$deprecations): bool {
            $deprecations[] = [$level, $message];
            return true;
        });

        try {
            $config = new TemporalConfig([
                'address' => 'legacy:7233',
                'temporalNamespace' => 'legacy-ns',
            ]);
        } finally {
            \restore_error_handler();
        }

        Assert::count($deprecations, 1);
        Assert::same($deprecations[0][0], \E_USER_DEPRECATED);
        Assert::same($config->getAddress(), 'legacy:7233');
        Assert::same($config->getTemporalNamespace(), 'legacy-ns');
        Assert::same($config->getClientConfig('default')->options->namespace, 'legacy-ns');
    }

    public function test_namespace_and_options_fall_back_to_legacy_keys_without_a_client(): void
    {
        $options = (new ClientOptions())->withNamespace('opts-ns');
        $config = new TemporalConfig([
            'temporalNamespace' => 'legacy-ns',
            'clientOptions' => $options,
        ]);

        Assert::same($config->getTemporalNamespace(), 'legacy-ns');
        Assert::same($config->getClientOptions(), $options);
    }

    public function test_legacy_address_keeps_client_options_and_other_clients(): void
    {
        $other = new ClientConfig(new ConnectionConfig('other:7233'));

        \set_error_handler(static fn(): bool => true, \E_USER_DEPRECATED);
        try {
            $config = new TemporalConfig([
                'address' => 'legacy:7233',
                'clientOptions' => (new ClientOptions())->withIdentity('worker-1'),
                'clients' => ['other' => $other],
            ]);
        } finally {
            \restore_error_handler();
        }

        Assert::same($config->getClientConfig('default')->options->identity, 'worker-1');
        Assert::same($config->getClientConfig('default')->options->namespace, 'default');
        Assert::same($config->getClientConfig('other'), $other);
    }

    public function test_legacy_client_options_must_be_client_options(): never
    {
        \set_error_handler(static fn(): bool => true, \E_USER_DEPRECATED);

        Expect::exception(\InvalidArgumentException::class)->withMessageContaining('`clientOptions`');

        try {
            new TemporalConfig(['address' => 'legacy:7233', 'clientOptions' => 'default']);
        } finally {
            \restore_error_handler();
        }
    }

    public function test_workers_are_normalized(): void
    {
        $options = WorkerOptions::new();
        $interceptor = \Mockery::mock(ExceptionInterceptorInterface::class);
        $config = new TemporalConfig([
            'workers' => [
                'plain' => $options,
                'full' => ['options' => $options, 'exception_interceptor' => $interceptor],
            ],
        ]);

        Assert::same($config->getWorkers(), [
            'plain' => $options,
            'full' => ['options' => $options, 'exception_interceptor' => $interceptor],
        ]);
    }

    #[DataSet([['queue' => 'options'], '`queue`'], 'worker is neither options nor an array')]
    #[DataSet([['queue' => ['options' => 'x']], 'Option `options`'], 'options of a wrong type')]
    #[DataSet([['queue' => ['exception_interceptor' => 5]], 'Option `exception_interceptor`'], 'interceptor of a wrong type')]
    #[DataSet([['queue' => ['exception_interceptor' => '']], 'Option `exception_interceptor`'], 'empty interceptor')]
    #[DataSet([[0 => WorkerOptions::class], '`workers`'], 'worker without a name')]
    public function test_invalid_workers_are_rejected(array $workers, string $message): never
    {
        Expect::exception(\InvalidArgumentException::class)->withMessageContaining($message);

        (new TemporalConfig(['workers' => $workers]))->getWorkers();
    }

    #[DataSet([''], 'empty')]
    #[DataSet([5], 'not a string')]
    public function test_invalid_default_client_is_rejected(mixed $client): never
    {
        Expect::exception(\InvalidArgumentException::class)->withMessageContaining('`client`');

        (new TemporalConfig(['client' => $client]))->getDefaultClient();
    }

    public function test_single_interceptor_is_accepted(): void
    {
        $config = new TemporalConfig(['interceptors' => 'App\\Interceptor']);

        Assert::same($config->getInterceptors(), ['App\\Interceptor', HandleActivityInterceptor::class]);
    }

    public function test_configured_interceptors_come_before_the_activity_interceptor(): void
    {
        $config = new TemporalConfig([
            'interceptors' => ['App\\FirstInterceptor'],
            'declarations' => ['App\\Workflow'],
            'workers' => ['queue' => ['exception_interceptor' => 'App\\Interceptor']],
        ]);

        Assert::same($config->getInterceptors(), ['App\\FirstInterceptor', HandleActivityInterceptor::class]);
        Assert::same($config->getDeclarations(), ['App\\Workflow']);
        Assert::same($config->getWorkers(), ['queue' => ['exception_interceptor' => 'App\\Interceptor']]);
    }
}
