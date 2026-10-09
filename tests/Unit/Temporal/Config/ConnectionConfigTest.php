<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel\Tests\Unit\Temporal\Config;

use Spiral\RoadRunnerLaravel\Temporal\Config\ClientConfig;
use Spiral\RoadRunnerLaravel\Temporal\Config\ConnectionConfig;
use Temporal\Client\GRPC\Context;
use Testo\Assert;
use Testo\Test;

#[Test]
final class ConnectionConfigTest
{
    public function test_plain_connection_is_not_secure(): void
    {
        $config = new ConnectionConfig('localhost:7233');

        Assert::false($config->isSecure());
        Assert::null($config->authToken);
    }

    public function test_with_tls_returns_a_secure_copy(): void
    {
        $plain = new ConnectionConfig('localhost:7233', authToken: 'token');
        $secure = $plain->withTls(
            rootCerts: '/root.pem',
            privateKey: '/key.pem',
            certChain: '/chain.pem',
            serverName: 'temporal.local',
        );

        Assert::false($plain->isSecure());
        Assert::true($secure->isSecure());
        Assert::same($secure->address, 'localhost:7233');
        Assert::same($secure->authToken, 'token');
        Assert::same($secure->tls->rootCerts, '/root.pem');
        Assert::same($secure->tls->privateKey, '/key.pem');
        Assert::same($secure->tls->certChain, '/chain.pem');
        Assert::same($secure->tls->serverName, 'temporal.local');
    }

    public function test_with_auth_key_keeps_address_and_tls(): void
    {
        $config = (new ConnectionConfig('localhost:7233'))->withTls()->withAuthKey('secret');

        Assert::same($config->authToken, 'secret');
        Assert::same($config->address, 'localhost:7233');
        Assert::true($config->isSecure());
    }

    public function test_client_config_uses_the_default_context(): void
    {
        $config = new ClientConfig(new ConnectionConfig('localhost:7233'));

        Assert::instanceOf($config->context, Context::class);
        Assert::same($config->options->namespace, 'default');
    }
}
