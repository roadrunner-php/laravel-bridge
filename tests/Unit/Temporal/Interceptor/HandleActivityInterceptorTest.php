<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel\Tests\Unit\Temporal\Interceptor;

use Illuminate\Container\Container;
use Illuminate\Contracts\Events\Dispatcher as DispatcherContract;
use Illuminate\Events\Dispatcher;
use Illuminate\Foundation\Application;
use Laravel\Octane\Events\WorkerStarting;
use Spiral\RoadRunnerLaravel\Temporal\Interceptor\HandleActivityInterceptor;
use Temporal\DataConverter\EncodedValues;
use Temporal\Interceptor\ActivityInbound\ActivityInput;
use Temporal\Interceptor\Header;
use Testo\Assert;
use Testo\Test;

#[Test]
final class HandleActivityInterceptorTest
{
    public function test_activity_result_is_returned(): void
    {
        $result = self::handle(static fn(): string => 'done');

        Assert::same($result, 'done');
    }

    public function test_activity_exception_is_rethrown(): void
    {
        $exception = new \DomainException('Activity failed', 42);

        try {
            self::handle(static fn() => throw $exception);
        } catch (\Throwable $e) {
            Assert::same($e, $exception);

            return;
        }

        Assert::fail('The activity exception was swallowed.');
    }

    public function test_worker_is_booted_once_per_application(): void
    {
        $app = new Application(\sys_get_temp_dir());
        $events = new Dispatcher($app);
        $app->instance(DispatcherContract::class, $events);
        $starts = 0;
        $events->listen(WorkerStarting::class, static function () use (&$starts): void {
            ++$starts;
        });
        $interceptor = new HandleActivityInterceptor();

        self::handle(static fn(): string => 'first', $interceptor, $app);
        self::handle(static fn(): string => 'second', $interceptor, $app);

        Assert::same($starts, 1);
    }

    public function test_failed_boot_is_retried_on_the_next_activity(): void
    {
        $app = new Application(\sys_get_temp_dir());
        $events = new Dispatcher($app);
        $app->instance(DispatcherContract::class, $events);
        $starts = 0;
        $events->listen(WorkerStarting::class, static function () use (&$starts): void {
            ++$starts === 1 and throw new \RuntimeException('Warm-up failed');
        });
        $interceptor = new HandleActivityInterceptor();

        $error = null;
        try {
            self::handle(static fn(): string => 'first', $interceptor, $app);
        } catch (\RuntimeException $e) {
            $error = $e;
        }

        Assert::same($error?->getMessage(), 'Warm-up failed');
        Assert::same(self::handle(static fn(): string => 'second', $interceptor, $app), 'second');
        Assert::same($starts, 2);
    }

    private static function handle(
        callable $activity,
        HandleActivityInterceptor $interceptor = new HandleActivityInterceptor(),
        Application $app = new Application(),
    ): mixed {
        $previous = Container::getInstance();
        $app->setBasePath(\sys_get_temp_dir());
        Container::setInstance($app);

        try {
            return $interceptor->handleActivityInbound(
                new ActivityInput(EncodedValues::empty(), Header::empty()),
                static fn(ActivityInput $input): mixed => $activity(),
            );
        } finally {
            Container::setInstance($previous);
        }
    }
}
