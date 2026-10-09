<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel\Tests\Unit\Grpc;

use RoadRunner\Jobs\DTO\V1\Stat;
use Spiral\Interceptors\Context\CallContextInterface;
use Spiral\Interceptors\HandlerInterface;
use Spiral\RoadRunner\GRPC\Context;
use Spiral\RoadRunner\GRPC\Exception\InvokeException;
use Spiral\RoadRunner\GRPC\Method;
use Spiral\RoadRunner\GRPC\StatusCode;
use Spiral\RoadRunnerLaravel\Grpc\Invoker;
use Spiral\RoadRunnerLaravel\Tests\Unit\Grpc\Fixture\PlainEchoService;
use Testo\Assert;
use Testo\Expect;
use Testo\Test;

#[Test]
final class InvokerTest
{
    public function test_input_is_decoded_and_the_result_is_serialized(): void
    {
        $service = new PlainEchoService();
        $ctx = new Context([]);
        $received = null;

        $handler = \Mockery::mock(HandlerInterface::class);
        $handler->shouldReceive('handle')->once()->andReturnUsing(
            static function (CallContextInterface $context) use (&$received): Stat {
                $received = $context;

                return new Stat(['pipeline' => 'out']);
            },
        );

        $input = (new Stat(['pipeline' => 'in']))->serializeToString();
        $result = (new Invoker($handler))->invoke($service, self::method('Echo'), $ctx, $input);

        $decoded = new Stat();
        $decoded->mergeFromString($result);
        Assert::same($decoded->getPipeline(), 'out');

        Assert::same($received->getTarget()->getObject(), $service);
        Assert::same($received->getTarget()->getReflection()->getName(), 'Echo');
        Assert::same($received->getArguments()[0], $ctx);
        Assert::same($received->getArguments()[1]->getPipeline(), 'in');
    }

    public function test_null_input_produces_an_empty_message(): void
    {
        $handler = \Mockery::mock(HandlerInterface::class);
        $handler->shouldReceive('handle')->once()->andReturnUsing(
            static fn(CallContextInterface $context): Stat => $context->getArguments()[1],
        );

        $result = (new Invoker($handler))->invoke(new PlainEchoService(), self::method('Echo'), new Context([]), null);

        Assert::same($result, '');
    }

    public function test_non_message_result_is_rejected(): never
    {
        $handler = \Mockery::mock(HandlerInterface::class);
        $handler->shouldReceive('handle')->andReturn('not a message');

        Expect::exception(InvokeException::class)
            ->withMessageContaining('must be an instance of')
            ->withCode(StatusCode::INTERNAL);

        (new Invoker($handler))->invoke(new PlainEchoService(), self::method('Echo'), new Context([]), null);
    }

    public function test_malformed_input_is_rejected(): never
    {
        $handler = \Mockery::mock(HandlerInterface::class);
        $handler->shouldReceive('handle')->never();

        Expect::exception(InvokeException::class)->withCode(StatusCode::INTERNAL);

        (new Invoker($handler))->invoke(new PlainEchoService(), self::method('Echo'), new Context([]), "\xFF\xFF\xFF");
    }

    private static function method(string $name): Method
    {
        return Method::parse(new \ReflectionMethod(PlainEchoService::class, $name));
    }
}
