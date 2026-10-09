<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel\Grpc;

use Google\Protobuf\Internal\Message;
use Spiral\Interceptors\Context\CallContext;
use Spiral\Interceptors\Context\Target;
use Spiral\Interceptors\HandlerInterface;
use Spiral\RoadRunner\GRPC\ContextInterface;
use Spiral\RoadRunner\GRPC\Exception\InvokeException;
use Spiral\RoadRunner\GRPC\InvokerInterface;
use Spiral\RoadRunner\GRPC\Method;
use Spiral\RoadRunner\GRPC\ServiceInterface;
use Spiral\RoadRunner\GRPC\StatusCode;

/**
 * @internal
 */
final class Invoker implements InvokerInterface
{
    public function __construct(
        private readonly HandlerInterface $handler,
    ) {}

    #[\Override]
    public function invoke(ServiceInterface $service, Method $method, ContextInterface $ctx, ?string $input): string
    {
        $message = $this->makeInput($method, $input);
        $handler = $this->handler;

        $result = $handler->handle(
            new CallContext(Target::fromPair($service, $method->name), [
                $ctx,
                $message,
            ]),
        );

        $result instanceof Message or throw new InvokeException(
            \sprintf(
                'The result of the Interceptors chain must be an instance of %s, %s given.',
                Message::class,
                \get_debug_type($result),
            ),
            StatusCode::INTERNAL,
        );

        return self::resultToString($result);
    }

    /**
     * Converts the result from the GRPC service method to the string.
     *
     * @throws InvokeException
     */
    private static function resultToString(Message $result): string
    {
        try {
            return $result->serializeToString();
        } catch (\Throwable $e) {
            throw InvokeException::create($e->getMessage(), StatusCode::INTERNAL, $e);
        }
    }

    /**
     * Converts the input from the GRPC service method to the Message object.
     *
     * @throws InvokeException
     */
    private function makeInput(Method $method, ?string $body): Message
    {
        try {
            $class = $method->inputType;

            /** @psalm-suppress UnsafeInstantiation */
            $in = new $class();

            if ($body !== null) {
                $in->mergeFromString($body);
            }

            return $in;
        } catch (\Throwable $e) {
            throw InvokeException::create($e->getMessage(), StatusCode::INTERNAL, $e);
        }
    }
}
