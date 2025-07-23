<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel\Queue;

use Illuminate\Contracts\Queue\Queue;
use Illuminate\Queue\Connectors\ConnectorInterface;
use Spiral\Goridge\RPC\Codec\ProtobufCodec;
use Spiral\Goridge\RPC\RPC;
use Spiral\RoadRunner\Environment;
use Spiral\RoadRunner\Jobs\Jobs;
use Spiral\RoadRunner\Jobs\KafkaOptions;
use Spiral\RoadRunner\Jobs\Options;
use Spiral\RoadRunner\Jobs\OptionsInterface;
use Spiral\RoadRunner\Jobs\Queue\Driver;

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
            $this->getOptions($config['options'] ?? []),
            $config['queue'],
        );
    }

    private function getOptions(array $config): OptionsInterface
    {
        $options = new Options(
            $config['delay'] ?? 0,
            $config['priority'] ?? 0,
            $config['auto_ack'] ?? false
        );

        return match ($config['driver'] ?? null) {
            Driver::Kafka => KafkaOptions::from($options)
                ->withTopic($config['topic'] ?? 'default'),
            default => $options,
        };
    }
}
