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
     */
    public function connect(array $config): Queue
    {
        $env = Environment::fromGlobals();

        $rpc = RPC::create($env->getRPCAddress())->withCodec(new ProtobufCodec());

        return new RoadRunnerQueue(
            new Jobs($rpc),
            $rpc,
            $config['queue'],
            $config['options'] ?? [],
        );
    }
}
