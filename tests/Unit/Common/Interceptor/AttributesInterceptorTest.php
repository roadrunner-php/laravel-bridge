<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel\Tests\Unit\Common\Interceptor;

use Spiral\Interceptors\Context\CallContext;
use Spiral\Interceptors\Context\CallContextInterface;
use Spiral\Interceptors\Context\Target;
use Spiral\Interceptors\HandlerInterface;
use Spiral\RoadRunnerLaravel\Common\Interceptor\AttributesInterceptor;
use Spiral\RoadRunnerLaravel\Tests\Unit\Grpc\Fixture\EchoService;
use Spiral\RoadRunnerLaravel\Tests\Unit\Grpc\Fixture\PlainEchoService;
use Spiral\RoadRunnerLaravel\Tests\Unit\Grpc\Fixture\RecordingInterceptor;
use Testo\Assert;
use Testo\Lifecycle\BeforeTest;
use Testo\Test;

#[Test]
final class AttributesInterceptorTest
{
    #[BeforeTest]
    public function resetLog(): void
    {
        RecordingInterceptor::$log = [];
    }

    public function test_class_interceptors_run_before_method_interceptors(): void
    {
        $result = (new AttributesInterceptor())->intercept(
            new CallContext(Target::fromPair(new EchoService(), 'Echo')),
            self::handler('handled'),
        );

        Assert::same($result, 'handled');
        Assert::same(RecordingInterceptor::$log, ['class', 'method']);
    }

    public function test_class_interceptors_apply_to_methods_without_attributes(): void
    {
        (new AttributesInterceptor())->intercept(
            new CallContext(Target::fromPair(new EchoService(), 'Plain')),
            self::handler('handled'),
        );

        Assert::same(RecordingInterceptor::$log, ['class']);
    }

    public function test_target_without_attributes_goes_straight_to_the_handler(): void
    {
        $result = (new AttributesInterceptor())->intercept(
            new CallContext(Target::fromPair(new PlainEchoService(), 'Echo')),
            self::handler('handled'),
        );

        Assert::same($result, 'handled');
        Assert::same(RecordingInterceptor::$log, []);
    }

    public function test_target_without_reflection_goes_straight_to_the_handler(): void
    {
        $result = (new AttributesInterceptor())->intercept(
            new CallContext(Target::fromPathString('service.method')),
            self::handler('handled'),
        );

        Assert::same($result, 'handled');
    }

    private static function handler(mixed $result): HandlerInterface
    {
        $handler = \Mockery::mock(HandlerInterface::class);
        $handler->shouldReceive('handle')->once()->with(\Mockery::type(CallContextInterface::class))->andReturn($result);

        return $handler;
    }
}
