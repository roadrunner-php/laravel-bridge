<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel;

use Throwable;
use Illuminate\Http\Request;
use Illuminate\Container\Container;
use Illuminate\Support\Facades\Facade;
use Spiral\RoadRunner\Http\PSR7Worker;
use Psr\Http\Message\ServerRequestInterface;
use Illuminate\Contracts\Http\Kernel as HttpKernelContract;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Events\Dispatcher as EventsDispatcher;
use Illuminate\Contracts\Foundation\Application as ApplicationContract;

/**
 * Idea is taken from the package: https://github.com/swooletw/laravel-swoole.
 */
class Worker implements WorkerInterface
{
    /**
     * Laravel application factory.
     */
    protected Application\FactoryInterface $app_factory;

    /**
     * PSR-7 Request/Response --> Symfony Request/Response.
     */
    protected \Symfony\Bridge\PsrHttpMessage\HttpFoundationFactoryInterface $http_factory_symfony;

    /**
     * Symfony Request/Response --> PSR-7 Request/Response.
     */
    protected \Symfony\Bridge\PsrHttpMessage\HttpMessageFactoryInterface $http_factory_psr7;

    /**
     * PSR-7 request factory.
     */
    protected \Psr\Http\Message\ServerRequestFactoryInterface $request_factory;

    /**
     * PSR-7 stream factory.
     */
    protected \Psr\Http\Message\StreamFactoryInterface $stream_factory;

    /**
     * PSR-7 uploads factory.
     */
    protected \Psr\Http\Message\UploadedFileFactoryInterface $uploads_factory;

    /**
     * Worker constructor.
     */
    public function __construct()
    {
        $this->app_factory = new Application\Factory();

        $this->http_factory_symfony = new \Symfony\Bridge\PsrHttpMessage\Factory\HttpFoundationFactory();

        $this->http_factory_psr7 = new \Symfony\Bridge\PsrHttpMessage\Factory\PsrHttpFactory(
            $this->request_factory = new \Laminas\Diactoros\ServerRequestFactory(),
            $this->stream_factory = new \Laminas\Diactoros\StreamFactory(),
            $this->uploads_factory = new \Laminas\Diactoros\UploadedFileFactory(),
            new \Laminas\Diactoros\ResponseFactory()
        );
    }

    /**
     * {@inheritdoc}
     */
    public function start(WorkerOptionsInterface $options): void
    {
        $psr7_worker = new \Spiral\RoadRunner\Http\PSR7Worker(
            new \Spiral\RoadRunner\Worker(\Spiral\Goridge\Relay::create($options->getRelayDsn())),
            $this->request_factory,
            $this->stream_factory,
            $this->uploads_factory
        );

        $app = $this->createApplication($options, $psr7_worker);

        $this->fireEvent($app, new Events\BeforeLoopStartedEvent($app));

        while ($req = $psr7_worker->waitRequest()) {
            if (!($req instanceof ServerRequestInterface)) { // termination request received
                break;
            }

            $responded = false;

            if ($options->getRefreshApp()) {
                $sandbox = $this->createApplication($options, $psr7_worker);
            } else {
                $sandbox = clone $app;
            }

            $this->setApplicationInstance($sandbox);

            /** @var HttpKernelContract $http_kernel */
            $http_kernel = $sandbox->make(HttpKernelContract::class);
            /** @var ConfigRepository $config */
            $config = $sandbox->make(ConfigRepository::class);

            try {
                $this->fireEvent($sandbox, new Events\BeforeLoopIterationEvent($sandbox, $req));
                $request = Request::createFromBase($this->http_factory_symfony->createRequest($req));

                $this->fireEvent($sandbox, new Events\BeforeRequestHandlingEvent($sandbox, $request));
                $response = $http_kernel->handle($request);
                $this->fireEvent($sandbox, new Events\AfterRequestHandlingEvent($sandbox, $request, $response));

                $psr7_worker->respond($this->http_factory_psr7->createResponse($response));
                $responded = true;
                $http_kernel->terminate($request, $response);

                $this->fireEvent($sandbox, new Events\AfterLoopIterationEvent($sandbox, $request, $response));
            } catch (Throwable $e) {
                if ($responded !== true) {
                    $psr7_worker->getWorker()->error($this->exceptionToString($e, $this->isDebugModeEnabled($config)));
                }

                $this->fireEvent($sandbox, new Events\LoopErrorOccurredEvent($sandbox, $req, $e));
            } finally {
                unset($http_kernel, $response, $request, $sandbox);

                $this->setApplicationInstance($app);
            }
        }

        $this->fireEvent($app, new Events\AfterLoopStoppedEvent($app));
    }

    /**
     * Create an Laravel application instance and bind all required instances.
     *
     * @param WorkerOptionsInterface $options
     * @param PSR7Worker             $psr7_worker
     *
     * @return ApplicationContract
     *
     * @throws Throwable
     */
    protected function createApplication(WorkerOptionsInterface $options, PSR7Worker $psr7_worker): ApplicationContract
    {
        $app = $this->app_factory->create($options->getAppBasePath());

        // Put PSR7 client into container
        $app->instance(PSR7Worker::class, $psr7_worker);

        return $app;
    }

    /**
     * @param Throwable $e
     * @param bool      $is_debug
     *
     * @return string
     */
    protected function exceptionToString(Throwable $e, bool $is_debug): string
    {
        return $is_debug
            ? (string) $e
            : 'Internal server error';
    }

    /**
     * @param ConfigRepository $config
     *
     * @return bool
     */
    protected function isDebugModeEnabled(ConfigRepository $config): bool
    {
        return $config->get('app.debug', false) === true;
    }

    /**
     * Set the current application in the container.
     *
     * @param ApplicationContract $app
     *
     * @return void
     */
    protected function setApplicationInstance(ApplicationContract $app): void
    {
        $app->instance('app', $app);
        $app->instance(Container::class, $app);

        Container::setInstance($app);

        Facade::clearResolvedInstances();
        Facade::setFacadeApplication($app);
    }

    /**
     * @param ApplicationContract $app
     * @param object              $event
     *
     * @return void
     */
    protected function fireEvent(ApplicationContract $app, object $event): void
    {
        /** @var EventsDispatcher $events */
        $events = $app->make(EventsDispatcher::class);

        $events->dispatch($event);
    }
}
