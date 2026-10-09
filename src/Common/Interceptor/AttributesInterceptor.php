<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel\Common\Interceptor;

use Spiral\Interceptors\Context\CallContextInterface;
use Spiral\Interceptors\Handler\InterceptorPipeline;
use Spiral\Interceptors\HandlerInterface;
use Spiral\Interceptors\InterceptorInterface;

/**
 * Interceptor for processing attributes that implement {@see InterceptorInterface}.
 *
 * It checks for the presence of attributes implementing {@see InterceptorInterface} at both class and method levels.
 * If such attributes are found, an interceptor pipeline is created that sequentially applies all found interceptors.
 *
 * Interceptors are applied in the order they are defined: first class attributes, then method attributes.
 *
 * Usage example:
 * ```
 *  #[LoggerInterceptor]
 *  #[AuthInterceptor]
 *  class PingService implements PingServiceInterface
 *  {
 *      #[AuthRoleInterceptor(role: 'admin')]
 *      public function Ping(
 *          GRPC\ContextInterface $ctx,
 *          PingRequest $in,
 *      ): PingResponse {
 *          return new PingResponse();
 *      }
 *  }
 * ```
 *
 * In this example, LoggerInterceptor and AuthInterceptor will be applied to all methods of the PingService class.
 * For the Ping method, AuthRoleInterceptor will additionally be applied.
 *
 * @author Aleksei Gagarin (roxblnfk)
 */
class AttributesInterceptor implements InterceptorInterface
{
    /**
     * @throws \Throwable
     */
    #[\Override]
    public function intercept(CallContextInterface $context, HandlerInterface $handler): mixed
    {
        $reflection = $context->getTarget()->getReflection();
        if ($reflection === null) {
            return $handler->handle($context);
        }

        $methodAttrs = $reflection->getAttributes(InterceptorInterface::class, \ReflectionAttribute::IS_INSTANCEOF);

        $classAttrs = $reflection instanceof \ReflectionMethod
            ? $reflection->getDeclaringClass()->getAttributes(InterceptorInterface::class, \ReflectionAttribute::IS_INSTANCEOF)
            : [];

        if ($methodAttrs === [] && $classAttrs === []) {
            return $handler->handle($context);
        }

        return (new InterceptorPipeline())
            ->withInterceptors(
                ...$this->resolveAttributes(...$classAttrs),
                ...$this->resolveAttributes(...$methodAttrs),
            )
            ->withHandler($handler)
            ->handle($context);
    }

    /**
     * @param \ReflectionAttribute<InterceptorInterface> ...$attributes
     * @return InterceptorInterface[]
     */
    private function resolveAttributes(\ReflectionAttribute ...$attributes): array
    {
        $result = [];
        foreach ($attributes as $attribute) {
            $result[] = $attribute->newInstance();
        }

        return $result;
    }
}
