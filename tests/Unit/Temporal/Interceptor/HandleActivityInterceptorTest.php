<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel\Tests\Unit\Temporal\Interceptor;

use Illuminate\Container\Container;
use Illuminate\Foundation\Application;
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

    private static function handle(callable $activity): mixed
    {
        $previous = Container::getInstance();
        Container::setInstance(new Application(\sys_get_temp_dir()));

        try {
            return (new HandleActivityInterceptor())->handleActivityInbound(
                new ActivityInput(EncodedValues::empty(), Header::empty()),
                static fn(ActivityInput $input): mixed => $activity(),
            );
        } finally {
            Container::setInstance($previous);
        }
    }
}
