<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel;

use Throwable;
use RuntimeException;
use Illuminate\Http\Request;
use InvalidArgumentException;
use Illuminate\Container\Container;
use Illuminate\Support\Facades\Facade;
use Spiral\RoadRunner\Http\PSR7Worker;
use Illuminate\Foundation\Bootstrap\RegisterProviders;
use Illuminate\Foundation\Bootstrap\SetRequestForConsole;
use Symfony\Bridge\PsrHttpMessage\Factory\PsrHttpFactory;
use Illuminate\Contracts\Http\Kernel as HttpKernelContract;
use Symfony\Bridge\PsrHttpMessage\HttpMessageFactoryInterface;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Events\Dispatcher as EventsDispatcher;
use Symfony\Bridge\PsrHttpMessage\HttpFoundationFactoryInterface;
use Illuminate\Contracts\Foundation\Application as ApplicationContract;

/**
 * Idea is taken from the package: https://github.com/swooletw/laravel-swoole.
 */
class Worker implements WorkerInterface
{
    /**
     * {@inheritdoc}
     */
    public function start(WorkerOptionsInterface $options): void
    {
        $http_foundation_factory = $this->createHttpFoundationFactory();

        $http_factory = new PsrHttpFactory(
            $request_factory = new \Laminas\Diactoros\ServerRequestFactory(),
            $stream_factory = new \Laminas\Diactoros\StreamFactory(),
            $uploads_factory = new \Laminas\Diactoros\UploadedFileFactory(),
            new \Laminas\Diactoros\ResponseFactory()
        );

        $psr7_worker = new \Spiral\RoadRunner\Http\PSR7Worker(
            $this->createWorker($options->getRelayDsn()),
            $request_factory,
            $stream_factory,
            $uploads_factory
        );

        $app = $this->createApplication($options->getAppBasePath());
        $this->bootstrapApplication($app, $psr7_worker);

        $this->fireEvent($app, new Events\BeforeLoopStartedEvent($app));

        while ($req = $psr7_worker->waitRequest()) {
            $responded = false;

            if ($options->getRefreshApp()) {
                $sandbox = $this->createApplication($options->getAppBasePath());
                $this->bootstrapApplication($sandbox, $psr7_worker);
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
                $request = Request::createFromBase($http_foundation_factory->createRequest($req));

                $this->fireEvent($sandbox, new Events\BeforeRequestHandlingEvent($sandbox, $request));
                $response = $http_kernel->handle($request);
                $this->fireEvent($sandbox, new Events\AfterRequestHandlingEvent($sandbox, $request, $response));

                $psr7_response = $http_factory->createResponse($response);
                $psr7_worker->respond($psr7_response);
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
     * Create the new application instance.
     *
     * @param string $base_path
     *
     * @return ApplicationContract
     * @throws InvalidArgumentException
     *
     */
    protected function createApplication(string $base_path): ApplicationContract
    {
        $path = \implode(\DIRECTORY_SEPARATOR, [\rtrim($base_path, \DIRECTORY_SEPARATOR), 'bootstrap', 'app.php']);

        if (!\is_file($path)) {
            throw new InvalidArgumentException("Application bootstrap file was not found in [{$path}]");
        }

        return require $path;
    }

    /**
     * Bootstrap passed application.
     *
     * @param ApplicationContract $app
     * @param PSR7Worker          $psr7_worker
     *
     * @return void
     * @throws RuntimeException
     *
     */
    protected function bootstrapApplication(ApplicationContract $app, PSR7Worker $psr7_worker): void
    {
        /** @var \Illuminate\Foundation\Http\Kernel $http_kernel */
        $http_kernel = $app->make(HttpKernelContract::class);

        $bootstrappers = $this->getKernelBootstrappers($http_kernel);

        // Insert `SetRequestForConsole` bootstrapper before `RegisterProviders` if it does not exists
        if (!\in_array(SetRequestForConsole::class, $bootstrappers, true)) {
            $register_index = (int) \array_search(RegisterProviders::class, $bootstrappers, true);

            if ($register_index !== false) {
                \array_splice($bootstrappers, $register_index, 0, [SetRequestForConsole::class]);
            }
        }

        // Method `bootstrapWith` declared in interface `\Illuminate\Contracts\Foundation\Application` since
        // `illuminate/contracts:v5.8` - https://git.io/Jvflt -> https://git.io/JvfOq
        if (\method_exists($app, $boot_method = 'bootstrapWith')) {
            $app->{$boot_method}($bootstrappers);
        } else {
            throw new RuntimeException("Required method [{$boot_method}] does not exists on application instance");
        }

        // Put PSR7 client into container
        $app->instance(PSR7Worker::class, $psr7_worker);

        $this->preResolveApplicationAbstracts($app);
    }

    /**
     * Make configured abstracts pre-resolving.
     *
     * @param ApplicationContract $app
     *
     * @return void
     */
    protected function preResolveApplicationAbstracts(ApplicationContract $app): void
    {
        /** @var ConfigRepository $config */
        $config = $app->make(ConfigRepository::class);

        // Pre-resolve instances
        foreach ((array) $config->get(ServiceProvider::getConfigRootKey() . '.pre_resolving', []) as $abstract) {
            if (\is_string($abstract) && $app->bound($abstract)) {
                $app->make($abstract);
            }
        }
    }

    /**
     * Get HTTP or Console kernel bootstrappers.
     *
     * @param \Illuminate\Foundation\Http\Kernel|\Illuminate\Foundation\Console\Kernel $kernel
     *
     * @return string[] Bootstrappers class names
     */
    protected function getKernelBootstrappers($kernel): array
    {
        ($method = (new \ReflectionObject($kernel))->getMethod($name = 'bootstrappers'))->setAccessible(true);

        return (array) $method->invoke($kernel);
    }

    /**
     * @param ApplicationContract $app
     * @param object              $event
     *
     * @return void
     */
    protected function fireEvent(ApplicationContract $app, $event): void
    {
        /** @var EventsDispatcher $events */
        $events = $app->make(EventsDispatcher::class);

        $events->dispatch($event);
    }

    /**
     * @param string $dsn Eg.: `pipes`, `pipes://stdin:stdout`, `tcp://localhost:6001`, `unix:///tmp/rpc.sock`
     *
     * @return \Spiral\RoadRunner\WorkerInterface
     */
    protected function createWorker(string $dsn): \Spiral\RoadRunner\WorkerInterface
    {
        return new \Spiral\RoadRunner\Worker(\Spiral\Goridge\Relay::create($dsn));
    }

    /**
     * @return HttpFoundationFactoryInterface
     */
    protected function createHttpFoundationFactory(): HttpFoundationFactoryInterface
    {
        return new \Symfony\Bridge\PsrHttpMessage\Factory\HttpFoundationFactory();
    }

    /**
     * @return HttpMessageFactoryInterface
     */
    protected function createPsr7Factory(): HttpMessageFactoryInterface
    {
        return new PsrHttpFactory(
            new \Laminas\Diactoros\ServerRequestFactory(),
            new \Laminas\Diactoros\StreamFactory(),
            new \Laminas\Diactoros\UploadedFileFactory(),
            new \Laminas\Diactoros\ResponseFactory()
        );
    }
}
