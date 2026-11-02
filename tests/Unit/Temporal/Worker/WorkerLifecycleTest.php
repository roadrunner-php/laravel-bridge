<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel\Tests\Unit\Temporal\Worker;

use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Facade;
use Spiral\RoadRunnerLaravel\ServiceProvider;
use Spiral\RoadRunnerLaravel\Temporal\Config\ClientConfig;
use Spiral\RoadRunnerLaravel\Temporal\Config\ConnectionConfig;
use Spiral\RoadRunnerLaravel\Temporal\TemporalServiceProvider;
use Spiral\RoadRunnerLaravel\Temporal\Worker\WorkersRegistryInterface;
use Temporal\Activity\ActivityInterface;
use Temporal\Activity\ActivityMethod;
use Temporal\DataConverter\EncodedValues;
use Temporal\DataConverter\ValuesInterface;
use Temporal\Interceptor\ActivityInbound\ActivityInput;
use Temporal\Interceptor\ActivityInboundInterceptor;
use Temporal\Interceptor\Header;
use Temporal\Worker\Transport\Command\ServerRequestInterface;
use Temporal\Worker\WorkerInterface;
use Temporal\Worker\WorkerOptions;
use Testo\Assert;
use Testo\Test;

#[Test]
final class WorkerLifecycleTest
{
    public function test_registered_worker_runs_interceptors_and_octane_sandbox(): void
    {
        $previous = Container::getInstance();
        $previousFacadeApplication = Facade::getFacadeApplication();
        $facadeCache = new \ReflectionProperty(Facade::class, 'resolvedInstance');
        $previousFacadeCache = $facadeCache->getValue();
        $app = new Application(\sys_get_temp_dir());
        $interceptor = new class implements ActivityInboundInterceptor {
            public int $calls = 0;

            public function handleActivityInbound(ActivityInput $input, callable $next): mixed
            {
                ++$this->calls;

                return $next($input);
            }
        };
        $app->instance('config', new Repository());
        Container::setInstance($app);

        try {
            $app->register(ServiceProvider::class);
            $app['config']->set('roadrunner.temporal', [
                'clients' => ['default' => new ClientConfig(new ConnectionConfig('127.0.0.1:7233'))],
                'workers' => ['probe' => ['options' => WorkerOptions::new()->withMaxConcurrentActivityExecutionSize(2)]],
                'interceptors' => [$interceptor],
            ]);
            $app->register(TemporalServiceProvider::class);
            $worker = $app->make(WorkersRegistryInterface::class)->get('probe');
            $failure = new \DomainException('Activity failed', 42);
            $app->instance(\DomainException::class, $failure);
            $worker->registerActivity(SandboxProbe::class, static fn() => $app->make(SandboxProbe::class));
            $result = self::invoke($worker, 'probe.sandbox');

            Assert::same([
                'activity_executed' => \is_int($result),
                'interceptor_calls' => $interceptor->calls,
                'sandbox_used' => $result !== \spl_object_id($app),
                'base_app_restored' => Container::getInstance() === $app,
                'activity_concurrency' => $worker->getOptions()->maxConcurrentActivityExecutionSize,
            ], [
                'activity_executed' => true,
                'interceptor_calls' => 1,
                'sandbox_used' => true,
                'base_app_restored' => true,
                'activity_concurrency' => 2,
            ]);

            try {
                self::invoke($worker, 'probe.fail');
                Assert::fail('The activity exception was swallowed.');
            } catch (\DomainException $exception) {
                Assert::same($exception, $failure);
            }

            Assert::same(Container::getInstance(), $app);
            Assert::same($interceptor->calls, 2);
            Assert::true(self::invoke($worker, 'probe.sandbox') !== \spl_object_id($app));
            Assert::same($interceptor->calls, 3);
            Assert::same(Container::getInstance(), $app);
        } finally {
            Facade::setFacadeApplication($previousFacadeApplication);
            $facadeCache->setValue(null, $previousFacadeCache);
            Container::setInstance($previous);
        }
    }

    private static function invoke(WorkerInterface $worker, string $activity): int
    {
        $request = \Mockery::mock(ServerRequestInterface::class);
        $request->shouldReceive('getName')->andReturn('InvokeActivity');
        $request->shouldReceive('getOptions')->andReturn(['info' => ['ActivityType' => ['Name' => $activity]]]);
        $request->shouldReceive('getPayloads')->andReturn(EncodedValues::empty());
        $request->shouldReceive('getHeader')->andReturn(Header::empty());
        $result = null;
        $error = null;
        $worker->dispatch($request, [])->then(
            static function (ValuesInterface $values) use (&$result): void {
                $result = $values->getValue(0, 'int');
            },
            static function (\Throwable $exception) use (&$error): void {
                $error = $exception;
            },
        );
        if ($error !== null) {
            throw $error;
        }

        Assert::true(\is_int($result));

        return $result;
    }
}

#[ActivityInterface(prefix: 'probe.')]
final class SandboxProbe
{
    public function __construct(private readonly \DomainException $failure) {}

    #[ActivityMethod]
    public function sandbox(): int
    {
        return \spl_object_id(Container::getInstance());
    }

    #[ActivityMethod]
    public function fail(): never
    {
        throw $this->failure;
    }
}
