<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel\Grpc;

use Spiral\RoadRunner\GRPC\ContextInterface;

interface GrpcServerInterceptorInterface
{
    public function intercept(string $method, ContextInterface $context, string $body, callable $next);
}
