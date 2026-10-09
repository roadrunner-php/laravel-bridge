<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel\Queue;

use Illuminate\Contracts\Queue\Queue;
use Illuminate\Queue\Connectors\ConnectorInterface;
use Spiral\Goridge\RPC\Codec\ProtobufCodec;
use Spiral\Goridge\RPC\RPC;
use Spiral\RoadRunner\Environment;
use Spiral\RoadRunner\Jobs\Jobs;

final class RoadRunnerConnector implements ConnectorInterface
{
    /**
     * Establish a queue connection.
     *
     * @param array<string, mixed> $config
     */
    #[\Override]
    public function connect(array $config): Queue
    {
        $env = Environment::fromGlobals();

        /** @var non-empty-string $rpcAddress */
        $rpcAddress = $env->getRPCAddress();
        $rpc = RPC::create($rpcAddress)->withCodec(new ProtobufCodec());

        $queue = $config['queue'] ?? null;
        \is_string($queue) && $queue !== '' or throw new \InvalidArgumentException(
            'The `queue` option of a RoadRunner queue connection must be a non-empty string.',
        );

        $options = $config['options'] ?? [];
        \is_array($options) or throw new \InvalidArgumentException(
            'The `options` option of a RoadRunner queue connection must be an array.',
        );

        return new RoadRunnerQueue(new Jobs($rpc), $rpc, $queue, $options);
    }
}
