<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel\Logging;

use Illuminate\Support\ServiceProvider;
use Psr\Log\LoggerInterface;
use RoadRunner\Logger\Logger as AppLogger;
use RoadRunner\PsrLogger\Context\DefaultProcessor;
use RoadRunner\PsrLogger\RpcLogger;
use Spiral\Goridge\RPC\Codec\ProtobufCodec;
use Spiral\Goridge\RPC\RPC;
use Spiral\RoadRunner\Environment;

final class LoggerServiceProvider extends ServiceProvider
{
    #[\Override]
    public function register(): void
    {
        $this->app->singleton(LoggerInterface::class, function () {
            $env = Environment::fromGlobals();

            /** @var non-empty-string $rpcAddress */
            $rpcAddress = $env->getRPCAddress();
            $rpc = RPC::create($rpcAddress)->withCodec(new ProtobufCodec());

            $appLogger = new AppLogger($rpc);

            $processor = DefaultProcessor::createDefault();

            return new RpcLogger($appLogger, $processor);
        });

        $this->app->alias(LoggerInterface::class, 'roadrunner.logger');
    }
}
