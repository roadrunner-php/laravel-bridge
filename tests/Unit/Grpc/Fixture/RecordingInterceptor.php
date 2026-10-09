<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel\Tests\Unit\Grpc\Fixture;

use Spiral\Interceptors\Context\CallContextInterface;
use Spiral\Interceptors\HandlerInterface;
use Spiral\Interceptors\InterceptorInterface;

#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD)]
final class RecordingInterceptor implements InterceptorInterface
{
    /** @var list<string> */
    public static array $log = [];

    public function __construct(
        private readonly string $name,
    ) {}

    public function intercept(CallContextInterface $context, HandlerInterface $handler): mixed
    {
        self::$log[] = $this->name;

        return $handler->handle($context);
    }
}
