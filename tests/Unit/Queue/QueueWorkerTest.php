<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel\Tests\Unit\Queue;

use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\Repository;
use Illuminate\Container\Container;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Queue\Events\JobExceptionOccurred;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Queue\MaxAttemptsExceededException;
use Illuminate\Queue\WorkerOptions;
use Illuminate\Support\Carbon;
use Spiral\RoadRunnerLaravel\Queue\QueueWorker;
use Spiral\RoadRunnerLaravel\Tests\Unit\Queue\Fixture\FakeJob;
use Testo\Assert;
use Testo\Lifecycle\AfterTest;
use Testo\Lifecycle\BeforeTest;
use Testo\Test;

#[Test]
final class QueueWorkerTest
{
    private const NOW = 1_700_000_000;

    /** @var list<object> */
    private array $events = [];

    /** @var list<\Throwable> */
    private array $reported = [];

    private Repository $cache;

    public function test_successful_job_is_fired_between_processing_events(): void
    {
        $job = new FakeJob();

        $this->worker()->process($job, new WorkerOptions(maxTries: 0));

        Assert::same($job->fired, 1);
        Assert::same($job->releases, []);
        Assert::same($this->reported, []);
        Assert::count($this->events, 2);
        Assert::instanceOf($this->events[0], JobProcessing::class);
        Assert::same($this->events[0]->connectionName, 'roadrunner');
        Assert::same($this->events[0]->job, $job);
        Assert::instanceOf($this->events[1], JobProcessed::class);
        Assert::same($this->events[1]->connectionName, 'roadrunner');
        Assert::same($this->events[1]->job, $job);
    }

    public function test_job_deleted_before_firing_is_not_fired(): void
    {
        $job = new FakeJob();
        $job->delete();

        $this->worker()->process($job, new WorkerOptions(maxTries: 0));

        Assert::same($job->fired, 0);
        Assert::count($this->events, 2);
        Assert::instanceOf($this->events[1], JobProcessed::class);
    }

    public function test_failing_job_is_reported_released_and_rethrown(): void
    {
        $exception = new \RuntimeException('boom');
        $job = self::throwingJob($exception, payload: ['backoff' => '5,15']);

        $thrown = $this->processExpectingException($job, new WorkerOptions(maxTries: 0));

        Assert::same($thrown, $exception);
        Assert::same($this->reported, [$exception]);
        Assert::same($job->releases, [5]);
        Assert::same($job->failures, []);
        Assert::count($this->events, 2);
        Assert::instanceOf($this->events[1], JobExceptionOccurred::class);
        Assert::same($this->events[1]->connectionName, 'roadrunner');
        Assert::same($this->events[1]->job, $job);
        Assert::same($this->events[1]->exception, $exception);
    }

    public function test_job_is_released_even_if_the_exception_event_listener_throws(): void
    {
        $listenerException = new \LogicException('listener');
        $job = self::throwingJob(new \RuntimeException('boom'));

        $events = \Mockery::mock(Dispatcher::class);
        $events->shouldReceive('dispatch')->andReturnUsing(static function (object $event) use ($listenerException): void {
            if ($event instanceof JobExceptionOccurred) {
                throw $listenerException;
            }
        });

        $thrown = $this->processExpectingException($job, new WorkerOptions(maxTries: 0), $this->worker($events));

        Assert::same($thrown, $listenerException);
        Assert::same($job->releases, [0]);
    }

    public function test_job_deleted_while_running_is_not_released(): void
    {
        $job = new FakeJob(onFire: static function (FakeJob $job): void {
            $job->delete();

            throw new \RuntimeException('boom');
        });

        $this->processExpectingException($job, new WorkerOptions(maxTries: 0));

        Assert::same($job->releases, []);
    }

    public function test_job_released_while_running_is_not_released_again(): void
    {
        $job = new FakeJob(onFire: static function (FakeJob $job): void {
            $job->release(30);

            throw new \RuntimeException('boom');
        });

        $this->processExpectingException($job, new WorkerOptions(maxTries: 0));

        Assert::same($job->releases, [30]);
    }

    public function test_job_that_already_exceeded_max_tries_is_failed_without_firing(): void
    {
        $job = new FakeJob(attempts: 3);

        $thrown = $this->processExpectingException($job, new WorkerOptions(maxTries: 2));

        Assert::instanceOf($thrown, MaxAttemptsExceededException::class);
        Assert::same(
            $thrown->getMessage(),
            'App\\Jobs\\Demo has been attempted too many times or run too long. The job may have previously timed out.',
        );
        Assert::same($job->failures, [$thrown]);
        Assert::same($job->fired, 0);
        Assert::same($job->releases, []);
    }

    public function test_job_at_max_tries_is_still_fired(): void
    {
        $job = new FakeJob(attempts: 2);

        $this->worker()->process($job, new WorkerOptions(maxTries: 2));

        Assert::same($job->fired, 1);
    }

    public function test_zero_max_tries_allows_unlimited_attempts(): void
    {
        $job = new FakeJob(attempts: 100);

        $this->worker()->process($job, new WorkerOptions(maxTries: 0));

        Assert::same($job->fired, 1);
    }

    public function test_job_max_tries_overrides_the_worker_option(): void
    {
        $job = new FakeJob(payload: ['maxTries' => 3], attempts: 4);

        $thrown = $this->processExpectingException($job, new WorkerOptions(maxTries: 0));

        Assert::instanceOf($thrown, MaxAttemptsExceededException::class);
        Assert::same($job->fired, 0);
    }

    public function test_job_within_retry_until_is_fired_regardless_of_attempts(): void
    {
        $job = new FakeJob(payload: ['retryUntil' => self::NOW], attempts: 5);

        $this->worker()->process($job, new WorkerOptions(maxTries: 1));

        Assert::same($job->fired, 1);
    }

    public function test_job_past_retry_until_is_failed_regardless_of_attempts(): void
    {
        $job = new FakeJob(payload: ['retryUntil' => self::NOW - 1]);

        $thrown = $this->processExpectingException($job, new WorkerOptions(maxTries: 0));

        Assert::instanceOf($thrown, MaxAttemptsExceededException::class);
        Assert::same($job->fired, 0);
    }

    public function test_failing_job_that_reaches_max_tries_is_failed_instead_of_released(): void
    {
        $exception = new \RuntimeException('boom');
        $job = self::throwingJob($exception, attempts: 2);

        $this->processExpectingException($job, new WorkerOptions(maxTries: 2));

        Assert::same($job->failures, [$exception]);
        Assert::same($job->releases, []);
    }

    public function test_failing_job_below_max_tries_is_released(): void
    {
        $job = self::throwingJob(new \RuntimeException('boom'), attempts: 1);

        $this->processExpectingException($job, new WorkerOptions(maxTries: 2));

        Assert::same($job->failures, []);
        Assert::same($job->releases, [0]);
    }

    public function test_failing_job_past_retry_until_is_failed_instead_of_released(): void
    {
        $exception = new \RuntimeException('boom');
        $job = new FakeJob(
            payload: ['retryUntil' => self::NOW + 1],
            onFire: static function () use ($exception): void {
                Carbon::setTestNow(Carbon::createFromTimestamp(self::NOW + 1));

                throw $exception;
            },
        );

        $this->processExpectingException($job, new WorkerOptions(maxTries: 0));

        Assert::same($job->failures, [$exception]);
        Assert::same($job->releases, []);
    }

    public function test_failing_job_within_retry_until_is_released(): void
    {
        $job = self::throwingJob(new \RuntimeException('boom'), attempts: 5, payload: ['retryUntil' => self::NOW + 60]);

        $this->processExpectingException($job, new WorkerOptions(maxTries: 1));

        Assert::same($job->failures, []);
        Assert::same($job->releases, [0]);
    }

    public function test_job_is_failed_once_it_reaches_max_exceptions(): void
    {
        $exception = new \RuntimeException('boom');
        $payload = ['uuid' => 'job-uuid', 'maxExceptions' => 2];
        $worker = $this->worker();

        $first = self::throwingJob($exception, payload: $payload);
        $this->processExpectingException($first, new WorkerOptions(maxTries: 0), $worker);

        Assert::same($first->failures, []);
        Assert::same($first->releases, [0]);
        Assert::same($this->cache->get('job-exceptions:job-uuid'), 1);

        $second = self::throwingJob($exception, attempts: 1, payload: $payload);
        $this->processExpectingException($second, new WorkerOptions(maxTries: 0), $worker);

        Assert::same($second->failures, [$exception]);
        Assert::same($second->releases, []);
        Assert::null($this->cache->get('job-exceptions:job-uuid'));
    }

    public function test_array_backoff_releases_the_job_with_the_delay_for_its_attempt(): void
    {
        $job = self::throwingJob(new \RuntimeException('boom'), attempts: 1);
        $this->processExpectingException($job, new WorkerOptions(backoff: [10, 30, 60], maxTries: 0));

        Assert::same($job->releases, [30]);

        $job = self::throwingJob(new \RuntimeException('boom'), attempts: 5);
        $this->processExpectingException($job, new WorkerOptions(backoff: [10, 30, 60], maxTries: 0));

        Assert::same($job->releases, [60]);
    }

    public function test_backoff_falls_back_to_the_last_delay_and_the_worker_option(): void
    {
        $job = self::throwingJob(new \RuntimeException('boom'), attempts: 5, payload: ['backoff' => '5,15']);
        $this->processExpectingException($job, new WorkerOptions(maxTries: 0));

        Assert::same($job->releases, [15]);

        $job = self::throwingJob(new \RuntimeException('boom'), attempts: 1);
        $this->processExpectingException($job, new WorkerOptions(backoff: 7, maxTries: 0));

        Assert::same($job->releases, [7]);
    }

    #[BeforeTest]
    public function setUp(): void
    {
        $this->events = [];
        $this->reported = [];
        $this->cache = new Repository(new ArrayStore());

        $handler = \Mockery::mock(ExceptionHandler::class);
        $handler->shouldReceive('report')->andReturnUsing(function (\Throwable $e): void {
            $this->reported[] = $e;
        });

        $container = new Container();
        $container->instance(ExceptionHandler::class, $handler);
        Container::setInstance($container);

        Carbon::setTestNow(Carbon::createFromTimestamp(self::NOW));
    }

    #[AfterTest]
    public function tearDown(): void
    {
        Container::setInstance(null);
        Carbon::setTestNow();
    }

    /**
     * @param array<string, mixed> $payload
     */
    private static function throwingJob(\Throwable $exception, int $attempts = 0, array $payload = []): FakeJob
    {
        return new FakeJob($payload, $attempts, static function () use ($exception): void {
            throw $exception;
        });
    }

    private function processExpectingException(FakeJob $job, WorkerOptions $options, ?QueueWorker $worker = null): \Throwable
    {
        try {
            ($worker ?? $this->worker())->process($job, $options);
        } catch (\Throwable $e) {
            return $e;
        }

        Assert::fail('process() was expected to rethrow the job exception.');
    }

    private function worker(?Dispatcher $events = null): QueueWorker
    {
        if ($events === null) {
            $events = \Mockery::mock(Dispatcher::class);
            $events->shouldReceive('dispatch')->andReturnUsing(function (object $event): void {
                $this->events[] = $event;
            });
        }

        $worker = new QueueWorker();

        (function (Dispatcher $events, Repository $cache): void {
            $this->events = $events;
            $this->cache = $cache;
        })->call($worker, $events, $this->cache);

        return $worker;
    }
}
