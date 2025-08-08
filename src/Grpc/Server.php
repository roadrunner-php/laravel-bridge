<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel\Grpc;

use Google\Protobuf\Any;
use Google\Rpc\Status;
use Spiral\RoadRunner\GRPC\Context;
use Spiral\RoadRunner\GRPC\ContextInterface;
use Spiral\RoadRunner\GRPC\Exception\GRPCException;
use Spiral\RoadRunner\GRPC\Exception\GRPCExceptionInterface;
use Spiral\RoadRunner\GRPC\Exception\NotFoundException;
use Spiral\RoadRunner\GRPC\Exception\ServiceException;
use Spiral\RoadRunner\GRPC\Internal\CallContext;
use Spiral\RoadRunner\GRPC\Internal\Json;
use Spiral\RoadRunner\GRPC\Invoker;
use Spiral\RoadRunner\GRPC\InvokerInterface;
use Spiral\RoadRunner\GRPC\ResponseHeaders;
use Spiral\RoadRunner\GRPC\ResponseTrailers;
use Spiral\RoadRunner\GRPC\ServiceInterface;
use Spiral\RoadRunner\GRPC\ServiceWrapper;
use Spiral\RoadRunner\GRPC\StatusCode;
use Spiral\RoadRunner\Payload;
use Spiral\RoadRunner\Worker;
use Spiral\RoadRunner\WorkerInterface;

/**
 * Manages group of services and communication with RoadRunner server.
 *
 * @psalm-type ServerOptions = array{
 *  debug?: bool
 * }
 */
final class Server
{
    /** @var ServiceWrapper[] */
    private array $services = [];

    /** @var class-string<GrpcServerInterceptorInterface>[] */
    private array $interceptors = [];

    /**
     * @param ServerOptions $options
     */
    public function __construct(
        private readonly \Laravel\Octane\Contracts\Worker $worker,
        private readonly InvokerInterface $invoker = new Invoker(),
        private readonly array $options = [],
    ) {}

    /**
     * Register new GRPC service.
     *
     * For example:
     * <code>
     *  $server->registerService(EchoServiceInterface::class, new EchoService());
     * </code>
     *
     * @template T of ServiceInterface
     *
     * @param class-string<T> $interface Generated service interface.
     * @param T $service Must implement interface.
     * @param array<class-string<GrpcServerInterceptorInterface>> $interceptors for this service. Must implement
     * GrpcServerInterceptorInterface.
     * @throws ServiceException
     */
    public function registerService(string $interface, ServiceInterface $service, array $interceptors = []): void
    {
        foreach ($interceptors as $interceptor) {
            if (!is_subclass_of($interceptor, GrpcServerInterceptorInterface::class)) {
                throw new ServiceException(
                    sprintf('Interceptor %s must implement %s', $interceptor, GrpcServerInterceptorInterface::class)
                );
            }
        }

        $service = new ServiceWrapper($this->invoker, $interface, $service);

        $this->services[$service->getName()] = $service;
        $this->interceptors[$service->getName()] = $interceptors;
    }

    /**
     * Serve GRPC over given RoadRunner worker.
     */
    public function serve(?WorkerInterface $worker = null, ?callable $finalize = null): void
    {
        $worker ??= Worker::create();

        while (true) {
            $request = $worker->waitPayload();

            if ($request === null) {
                return;
            }

            $this->worker->handleTask(function () use ($request, $worker, $finalize): void {
                $responseHeaders = new ResponseHeaders();
                $responseTrailers = new ResponseTrailers();

                try {
                    $call = CallContext::decode($request->header);

                    $context = new Context(
                        \array_merge(
                            $call->context,
                            [
                                ResponseHeaders::class => $responseHeaders,
                                ResponseTrailers::class => $responseTrailers,
                            ],
                        ),
                    );

                    $response = $this->invoke($call->service, $call->method, $context, $request->body);

                    $headers = [];
                    $responseHeaders->count() === 0 or $headers['headers'] = $responseHeaders->packHeaders();
                    $responseTrailers->count() === 0 or $headers['trailers'] = $responseTrailers->packTrailers();

                    $this->workerSend(
                        worker: $worker,
                        body: $response,
                        headers: $headers === [] ? '{}' : Json::encode($headers),
                    );
                } catch (GRPCExceptionInterface $e) {
                    $headers = [
                        'error' => $this->createGrpcError($e),
                    ];
                    $responseHeaders->count() === 0 or $headers['headers'] = $responseHeaders->packHeaders();
                    $responseTrailers->count() === 0 or $headers['trailers'] = $responseTrailers->packTrailers();

                    $this->workerSend(
                        worker: $worker,
                        body: '',
                        headers: Json::encode($headers),
                    );
                } catch (\Throwable $e) {
                    report($e);
                    $this->workerError($worker, $this->isDebugMode() ? (string) $e : $e->getMessage());
                } finally {
                    if ($finalize !== null) {
                        isset($e) ? $finalize($e) : $finalize();
                    }
                }
            });
        }
    }

    /**
     * Invoke service method with binary payload and return the response.
     *
     * @param class-string<ServiceInterface> $serviceName
     * @param non-empty-string $method
     * @throws GRPCException
     */
    protected function invoke(string $serviceName, string $method, ContextInterface $context, string $body): string
    {
        if (!isset($this->services[$serviceName])) {
            throw NotFoundException::create("Service `{$serviceName}` not found.", StatusCode::NOT_FOUND);
        }

        $service = $this->services[$serviceName];
        $interceptors = $this->interceptors[$serviceName] ?? [];

        $handler = function ($method, $context, $body) use ($service) {
            return $service->invoke($method, $context, $body);
        };

        $pipeline = array_reduce(
            array_reverse($interceptors),
            fn($next, $interceptor) => fn($method, $context, $body) => (new $interceptor)->intercept($method, $context, $body, $next),
            $handler
        );

        return $pipeline($method, $context, $body);
    }

    private function workerError(WorkerInterface $worker, string $message): void
    {
        $worker->error($message);
    }

    /**
     * @psalm-suppress InaccessibleMethod
     */
    private function workerSend(WorkerInterface $worker, string $body, string $headers): void
    {
        $worker->respond(new Payload($body, $headers));
    }

    private function createGrpcError(GRPCExceptionInterface $e): string
    {
        $status = new Status([
            'code' => $e->getCode(),
            'message' => $e->getMessage(),
            'details' => \array_map(
                static function ($detail) {
                    $message = new Any();
                    $message->pack($detail);

                    return $message;
                },
                $e->getDetails(),
            ),
        ]);

        return \base64_encode((string) $status->serializeToString());
    }

    /**
     * Checks if debug mode is enabled.
     */
    private function isDebugMode(): bool
    {
        $debug = false;

        if (isset($this->options['debug'])) {
            $debug = \filter_var($this->options['debug'], \FILTER_VALIDATE_BOOLEAN);
        }

        return $debug;
    }
}
