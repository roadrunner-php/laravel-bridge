<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel\Tests\Unit\Queue;

use Mockery\MockInterface;
use Testo\Data\DataProvider;
use Testo\Test;
use Testo\Assert;
use Illuminate\Support\Carbon;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\ManuallyFailedException;
use Spiral\RoadRunner\Jobs\Task\ReceivedTaskInterface;
use Spiral\RoadRunnerLaravel\Queue\RoadRunnerJob;

#[Test]
final class RoadRunnerJobTest
{
    private const NOW = 1_700_000_000;

    public static function releaseDelays(): array
    {
        return [
            'DateInterval' => [new \DateInterval('PT30S'), 30],
            'future DateTimeInterface' => [new \DateTimeImmutable('@' . (self::NOW + 45)), 45],
            'past DateTimeInterface' => [new \DateTimeImmutable('@' . (self::NOW - 10)), 0],
            'negative seconds' => [-5, 0],
        ];
    }

    public static function nonObjectPayloads(): array
    {
        return [
            'invalid JSON' => ['not-json'],
            'empty body' => [''],
            'JSON null' => ['null'],
            'JSON string' => ['"text"'],
            'JSON number' => ['42'],
        ];
    }

    public function test_get_raw_body_returns_the_wire_payload_string_verbatim(): void
    {
        // Deliberately use non-canonical JSON (spaces after `:` and `,`) so a
        // regression that round-trips through json_decode + json_encode would
        // produce different bytes and fail the byte-identity assertion below.
        $wirePayload = '{"job": "Illuminate\\\\Queue\\\\CallQueuedHandler@call", "data": {"commandName": "App\\\\Jobs\\\\Demo"}}';

        $task = \Mockery::mock(ReceivedTaskInterface::class)->shouldIgnoreMissing();
        $task->shouldReceive('getPayload')->andReturn($wirePayload);

        $job = new RoadRunnerJob(\Mockery::mock(Application::class)->shouldIgnoreMissing(), $task);

        $body = $job->getRawBody();

        Assert::true(\is_string($body), 'getRawBody() is part of the Illuminate\\Contracts\\Queue\\Job contract and MUST return a string.');
        Assert::same($body, $wirePayload, 'getRawBody() should return the raw bytes the task carried on the wire, not a re-encoded version.');
    }

    public function test_get_raw_body_is_consistent_with_decoded_payload(): void
    {
        // Sanity-check that getRawBody() and payload() agree on the data they
        // describe — they're two views of the same body (string vs decoded
        // array), and any divergence here would mean failure handlers
        // (FailingJob), tracing integrations, and retry serialization all see
        // different pictures of the same job.
        $wirePayload = '{"job":"X","data":{"foo":"bar"},"attempts":0}';

        $task = \Mockery::mock(ReceivedTaskInterface::class)->shouldIgnoreMissing();
        $task->shouldReceive('getPayload')->andReturn($wirePayload);

        $job = new RoadRunnerJob(\Mockery::mock(Application::class)->shouldIgnoreMissing(), $task);

        Assert::same($job->getRawBody(), $wirePayload);
        Assert::same($job->payload(), \json_decode($job->getRawBody(), true));
    }

    public function test_job_id_and_attempts_come_from_the_task(): void
    {
        $task = self::task('{"job":"X","data":{}}');
        $task->shouldReceive('getId')->andReturn('task-1');
        $task->shouldReceive('getHeaderLine')->with('attempts')->andReturn('3');

        $job = new RoadRunnerJob(\Mockery::mock(Application::class), $task);

        Assert::same($job->getJobId(), 'task-1');
        Assert::same($job->attempts(), 3);
    }

    public function test_attempts_is_zero_without_the_header(): void
    {
        $task = self::task('{"job":"X","data":{}}');
        $task->shouldReceive('getHeaderLine')->with('attempts')->andReturn('');

        $job = new RoadRunnerJob(\Mockery::mock(Application::class), $task);

        Assert::same($job->attempts(), 0);
    }

    public function test_fire_calls_the_handler_and_completes_the_task(): void
    {
        $handler = new class {
            public array $calls = [];

            public function handle(object $job, mixed $data): void
            {
                $this->calls[] = [$job, $data];
            }
        };

        $app = \Mockery::mock(Application::class);
        $app->shouldReceive('make')->once()->with('App\\Handler')->andReturn($handler);

        $task = self::task('{"job":"App\\\\Handler@handle","data":{"foo":"bar"}}');
        $task->shouldReceive('complete')->once();

        $job = new RoadRunnerJob($app, $task);
        $job->fire();

        Assert::count($handler->calls, 1);
        Assert::same($handler->calls[0][0], $job);
        Assert::same($handler->calls[0][1], ['foo' => 'bar']);
    }

    public function test_release_requeues_the_task_with_delay_and_next_attempt(): void
    {
        $task = self::task('{"job":"X","data":{}}');
        $task->shouldReceive('getHeaderLine')->with('attempts')->andReturn('1');
        $task->shouldReceive('withDelay')->once()->with(30)->andReturnSelf();
        $task->shouldReceive('withHeader')->once()->with('attempts', '2')->andReturnSelf();
        $task->shouldReceive('requeue')->once()->with('release');

        $job = new RoadRunnerJob(\Mockery::mock(Application::class), $task);
        $job->release(30);

        Assert::true($job->isReleased());
    }

    #[DataProvider('releaseDelays')]
    public function test_release_converts_the_delay_to_non_negative_seconds(\DateInterval|\DateTimeInterface|int $delay, int $seconds): void
    {
        $task = self::task('{"job":"X","data":{}}');
        $task->shouldReceive('getHeaderLine')->with('attempts')->andReturn('1');
        $task->shouldReceive('withDelay')->once()->with($seconds)->andReturnSelf();
        $task->shouldReceive('withHeader')->once()->with('attempts', '2')->andReturnSelf();
        $task->shouldReceive('requeue')->once()->with('release');

        $job = new RoadRunnerJob(\Mockery::mock(Application::class), $task);

        Carbon::setTestNow(Carbon::createFromTimestamp(self::NOW));
        try {
            $job->release($delay);
        } finally {
            Carbon::setTestNow();
        }

        Assert::true($job->isReleased());
    }

    public function test_fail_marks_the_task_failed_and_notifies_the_job_handler(): void
    {
        $exception = new \RuntimeException('boom');
        $handler = new class {
            public array $failed = [];

            public function failed(mixed $data, \Throwable $e): void
            {
                $this->failed[] = [$data, $e];
            }
        };

        $events = \Mockery::mock(Dispatcher::class);
        $events->shouldReceive('dispatch')->once()->with(\Mockery::on(
            static fn(mixed $event): bool => $event instanceof JobFailed && $event->exception === $exception,
        ));

        $app = \Mockery::mock(Application::class);
        $app->shouldReceive('make')->with('App\\Handler')->andReturn($handler);
        $app->shouldReceive('make')->with(Dispatcher::class)->andReturn($events);

        $task = self::task('{"job":"App\\\\Handler@handle","data":{"foo":"bar"}}');
        $task->shouldReceive('getHeaderLine')->with('attempts')->andReturn('1');
        $task->shouldReceive('withHeader')->once()->with('attempts', '2')->andReturnSelf();
        $task->shouldReceive('fail')->once()->with('boom');

        $job = new RoadRunnerJob($app, $task);
        $job->fail($exception);

        Assert::true($job->hasFailed());
        Assert::true($job->isDeleted());
        Assert::count($handler->failed, 1);
        Assert::same($handler->failed[0][0], ['foo' => 'bar']);
        Assert::same($handler->failed[0][1], $exception);
    }

    public function test_fail_without_an_exception_marks_the_task_failed(): void
    {
        $handler = new class {
            public array $failed = [];

            public function failed(mixed $data, ?\Throwable $e): void
            {
                $this->failed[] = [$data, $e];
            }
        };

        $events = \Mockery::mock(Dispatcher::class);
        $events->shouldReceive('dispatch')->once()->with(\Mockery::on(
            static fn(mixed $event): bool => $event instanceof JobFailed && $event->exception instanceof ManuallyFailedException,
        ));

        $app = \Mockery::mock(Application::class);
        $app->shouldReceive('make')->with('App\\Handler')->andReturn($handler);
        $app->shouldReceive('make')->with(Dispatcher::class)->andReturn($events);

        $task = self::task('{"job":"App\\\\Handler@handle","data":{"foo":"bar"}}');
        $task->shouldReceive('getHeaderLine')->with('attempts')->andReturn('1');
        $task->shouldReceive('withHeader')->once()->with('attempts', '2')->andReturnSelf();
        $task->shouldReceive('fail')->once()->with(\Mockery::type('string'));

        $job = new RoadRunnerJob($app, $task);
        $job->fail();

        Assert::true($job->hasFailed());
        Assert::true($job->isDeleted());
        Assert::count($handler->failed, 1);
        Assert::same($handler->failed[0], [['foo' => 'bar'], null]);
    }

    #[DataProvider('nonObjectPayloads')]
    public function test_payload_is_empty_for_a_non_object_body(string $body): void
    {
        $job = new RoadRunnerJob(\Mockery::mock(Application::class), self::task($body));

        Assert::same($job->payload(), []);
    }

    private static function task(string $payload): MockInterface&ReceivedTaskInterface
    {
        $task = \Mockery::mock(ReceivedTaskInterface::class);
        $task->shouldReceive('getPayload')->andReturn($payload);

        return $task;
    }
}
