<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel\Temporal\Interceptor;

use Illuminate\Container\Container;
use Illuminate\Foundation\Application;
use Laravel\Octane\ApplicationFactory;
use Laravel\Octane\Exceptions\TaskExceptionResult;
use Laravel\Octane\Swoole\TaskResult;
use Spiral\RoadRunnerLaravel\OctaneWorker;
use Temporal\Interceptor\ActivityInbound\ActivityInput;
use Temporal\Interceptor\ActivityInboundInterceptor;

final class HandleActivityInterceptor implements ActivityInboundInterceptor
{
    private ?OctaneWorker $worker = null;

    #[\Override]
    public function handleActivityInbound(ActivityInput $input, callable $next): mixed
    {
        $worker = $this->getWorker();

        /** @var \Throwable|null $exception */
        $exception = null;
        /** @var TaskResult|TaskExceptionResult $result */
        $result = $worker->handleTask(static function () use ($next, $input, &$exception): mixed {
            try {
                return $next($input);
            } catch (\Throwable $e) {
                throw $exception = $e;
            }
        });

        if ($result instanceof TaskExceptionResult) {
            // Octane flattens the failure into a TaskException; Temporal needs the original
            // instance to keep its type for retry policies and failure details.
            throw $exception ?? $result->getOriginal();
        }

        return $result->result;
    }

    /**
     * Boots once per base application, so `WorkerStarting` is not dispatched for every activity.
     */
    private function getWorker(): OctaneWorker
    {
        /** @var Application $app */
        $app = Container::getInstance();

        if ($this->worker?->application() === $app) {
            return $this->worker;
        }

        // Cache only after a successful boot, so a failing WorkerStarting listener is retried.
        $worker = new OctaneWorker(appFactory: new ApplicationFactory($app->basePath()));
        $worker->boot(application: $app);

        return $this->worker = $worker;
    }
}
