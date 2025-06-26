<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel\Queue;

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Contracts\Events\Dispatcher as EventDispatcher;
use Illuminate\Queue\Events\JobExceptionOccurred;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Queue\MaxAttemptsExceededException;
use Illuminate\Queue\WorkerOptions;
use Illuminate\Support\Carbon;
use Laravel\Octane\ApplicationFactory;
use Spiral\RoadRunner\Jobs\Consumer;
use Spiral\RoadRunnerLaravel\OctaneWorker;
use Spiral\RoadRunnerLaravel\WorkerInterface;
use Spiral\RoadRunnerLaravel\WorkerOptionsInterface;

final class QueueWorker implements WorkerInterface
{
    /** The event dispatcher instance. */
    protected EventDispatcher $events;

    /**  The cache repository implementation.*/
    protected CacheRepository $cache;

    private readonly string $connectionName;

    public function __construct()
    {
        $this->connectionName = 'roadrunner';
    }

    public function start(WorkerOptionsInterface $options): void
    {
        $worker = new OctaneWorker(
            appFactory: new ApplicationFactory($options->getAppBasePath()),
        );

        $options = new WorkerOptions();

        $worker->boot();

        $consumer = new Consumer();
        while ($task = $consumer->waitTask()) {
            $app = $worker->application();
            $worker->handleTask(function () use ($app, $task, $options): void {
                $this->events = $app->get(EventDispatcher::class);
                $this->cache = $app->get(CacheRepository::class);

                $this->runJob(
                    new RoadRunnerJob($app, $task),
                    $options,
                );
            });
        }
    }

    /**
     * Process the given job from the queue.
     *
     * @throws \Throwable
     */
    public function process(RoadRunnerJob $job, WorkerOptions $options): void
    {
        try {
            // First we will raise the before job event and determine if the job has already ran
            // over its maximum attempt limits, which could primarily happen when this job is
            // continually timing out and not actually throwing any exceptions from itself.
            $this->raiseBeforeJobEvent($job);

            $this->markJobAsFailedIfAlreadyExceedsMaxAttempts(
                $job,
                (int) $options->maxTries ?? 10,
            );

            if ($job->isDeleted()) {
                $this->raiseAfterJobEvent($job);

                return;
            }

            // Here we will fire off the job and let it process. We will catch any exceptions so
            // they can be reported to the developers logs, etc. Once the job is finished the
            // proper events will be fired to let any listeners know this job has finished.
            $job->fire();

            $this->raiseAfterJobEvent($job);
        } catch (\Throwable $e) {
            report($e);

            $this->handleJobException($job, $options, $e);
        }
    }

    /**
     * Process the given job.
     */
    protected function runJob(RoadRunnerJob $job, WorkerOptions $options): void
    {
        try {
            $this->process($job, $options);
        } catch (\Throwable $e) {
            report($e);
        }
    }

    /**
     * Raise the before queue job event.
     */
    protected function raiseBeforeJobEvent(RoadRunnerJob $job): void
    {
        $this->events->dispatch(
            new JobProcessing(
                $this->connectionName,
                $job,
            ),
        );
    }

    /**
     * Raise the after queue job event.
     */
    protected function raiseAfterJobEvent(RoadRunnerJob $job): void
    {
        $this->events->dispatch(
            new JobProcessed(
                $this->connectionName,
                $job,
            ),
        );
    }

    /**
     * Raise the exception occurred queue job event.
     */
    protected function raiseExceptionOccurredJobEvent(RoadRunnerJob $job, \Throwable $e): void
    {
        $this->events->dispatch(
            new JobExceptionOccurred(
                $this->connectionName,
                $job,
                $e,
            ),
        );
    }

    /**
     * Mark the given job as failed if it has exceeded the maximum allowed attempts.
     *
     * This will likely be because the job previously exceeded a timeout.
     *
     * @throws \Throwable
     */
    protected function markJobAsFailedIfAlreadyExceedsMaxAttempts(RoadRunnerJob $job, int $maxTries): void
    {
        $maxTries = !\is_null($job->maxTries()) ? $job->maxTries() : $maxTries;

        $retryUntil = $job->retryUntil();

        if ($retryUntil && Carbon::now()->getTimestamp() <= $retryUntil) {
            return;
        }

        if (!$retryUntil && ($maxTries === 0 || $job->attempts() <= $maxTries)) {
            return;
        }

        $this->failJob($job, $e = $this->maxAttemptsExceededException($job));

        throw $e;
    }

    /**
     * Mark the given job as failed and raise the relevant event.
     */
    protected function failJob(RoadRunnerJob $job, \Throwable $e): void
    {
        $job->fail($e);
    }

    /**
     * Create an instance of MaxAttemptsExceededException.
     */
    protected function maxAttemptsExceededException(RoadRunnerJob $job): MaxAttemptsExceededException
    {
        return new MaxAttemptsExceededException(
            $job->resolveName() . ' has been attempted too many times or run too long. The job may have previously timed out.',
        );
    }

    /**
     * Handle an exception that occurred while the job was running.
     *
     * @throws \Throwable
     */
    protected function handleJobException(RoadRunnerJob $job, WorkerOptions $options, \Throwable $e): void
    {
        try {
            // First, we will go ahead and mark the job as failed if it will exceed the maximum
            // attempts it is allowed to run the next time we process it. If so we will just
            // go ahead and mark it as failed now so we do not have to release this again.
            if (!$job->hasFailed()) {
                $this->markJobAsFailedIfWillExceedMaxAttempts($job, (int) $options->maxTries, $e);
                $this->markJobAsFailedIfWillExceedMaxExceptions($job, $e);
            }

            $this->raiseExceptionOccurredJobEvent($job, $e);
        } finally {
            // If we catch an exception, we will attempt to release the job back onto the queue
            // so it is not lost entirely. This'll let the job be retried at a later time by
            // another listener (or this same one). We will re-throw this exception after.
            if (!$job->isDeleted() && !$job->isReleased() && !$job->hasFailed()) {
                $job->release($this->calculateBackoff($job, $options));
            }
        }

        throw $e;
    }

    /**
     * Mark the given job as failed if it has exceeded the maximum allowed attempts.
     */
    protected function markJobAsFailedIfWillExceedMaxAttempts(RoadRunnerJob $job, int $maxTries, \Throwable $e): void
    {
        $maxTries = !\is_null($job->maxTries()) ? $job->maxTries() : $maxTries;

        if ($job->retryUntil() && $job->retryUntil() <= Carbon::now()->getTimestamp()) {
            $this->failJob($job, $e);
        }

        if (!$job->retryUntil() && $maxTries > 0 && $job->attempts() >= $maxTries) {
            $this->failJob($job, $e);
        }
    }

    /**
     * Mark the given job as failed if it has exceeded the maximum allowed attempts.
     */
    protected function markJobAsFailedIfWillExceedMaxExceptions(RoadRunnerJob $job, \Throwable $e): void
    {
        if (!$this->cache || \is_null($uuid = $job->uuid()) ||
            \is_null($maxExceptions = $job->maxExceptions())) {
            return;
        }

        if (!$this->cache->get('job-exceptions:' . $uuid)) {
            $this->cache->put('job-exceptions:' . $uuid, 0, Carbon::now()->addDay());
        }

        if ($maxExceptions <= $this->cache->increment('job-exceptions:' . $uuid)) {
            $this->cache->forget('job-exceptions:' . $uuid);

            $this->failJob($job, $e);
        }
    }

    /**
     * Calculate the backoff for the given job.
     */
    protected function calculateBackoff(RoadRunnerJob $job, WorkerOptions $options): int
    {
        $backoff = \explode(
            ',',
            \method_exists($job, 'backoff') && !\is_null($job->backoff())
                ? $job->backoff()
                : (string) $options->backoff,
        );

        return (int) ($backoff[$job->attempts()] ?? last($backoff));
    }
}
