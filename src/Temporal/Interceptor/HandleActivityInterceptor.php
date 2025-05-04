<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel\Temporal\Interceptor;

use Illuminate\Container\Container;
use Illuminate\Foundation\Application;
use Laravel\Octane\ApplicationFactory;
use Spiral\RoadRunnerLaravel\OctaneWorker;
use Temporal\Interceptor\ActivityInbound\ActivityInput;
use Temporal\Interceptor\ActivityInboundInterceptor;

final readonly class HandleActivityInterceptor implements ActivityInboundInterceptor
{
    public function handleActivityInbound(ActivityInput $input, callable $next): mixed
    {
        /** @var Application $app */
        $app = Container::getInstance();

        $worker = new OctaneWorker(
            appFactory: new ApplicationFactory($app->basePath()),
        );

        $worker->boot(application: $app);

        return $worker->handleTask(static fn() => $next($input))->result;
    }
}
