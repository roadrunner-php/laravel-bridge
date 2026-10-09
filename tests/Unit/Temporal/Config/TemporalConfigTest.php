<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel\Tests\Unit\Temporal\Config;

use Spiral\RoadRunnerLaravel\Temporal\Config\ClientConfig;
use Spiral\RoadRunnerLaravel\Temporal\Config\ConnectionConfig;
use Spiral\RoadRunnerLaravel\Temporal\Config\TemporalConfig;
use Spiral\RoadRunnerLaravel\Temporal\Interceptor\HandleActivityInterceptor;
use Temporal\Client\ClientOptions;
use Temporal\Worker\WorkerFactoryInterface;
use Testo\Assert;
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

    public function test_configured_interceptors_come_before_the_activity_interceptor(): void
    {
        $config = new TemporalConfig([
            'interceptors' => ['App\\FirstInterceptor'],
            'declarations' => ['App\\Workflow'],
            'workers' => ['queue' => ['options' => null]],
        ]);

        Assert::same($config->getInterceptors(), ['App\\FirstInterceptor', HandleActivityInterceptor::class]);
        Assert::same($config->getDeclarations(), ['App\\Workflow']);
        Assert::same($config->getWorkers(), ['queue' => ['options' => null]]);
    }
}
