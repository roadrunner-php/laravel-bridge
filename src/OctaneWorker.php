<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel;

use Illuminate\Foundation\Application;
use Laravel\Octane\ApplicationFactory;
use Laravel\Octane\Contracts\Client;
use Laravel\Octane\Events\WorkerStarting;
use Laravel\Octane\Worker;

final class OctaneWorker extends Worker
{
    public function __construct(
        protected ApplicationFactory $appFactory,
        ?Client $client = null,
    ) {
        parent::__construct($appFactory, $client ?? new DummyClient());
    }

    public function boot(array $initialInstances = [], ?Application $application = null): void
    {
        // First we will create an instance of the Laravel application that can serve as
        // the base container instance we will clone from on every request. This will
        // also perform the initial bootstrapping that's required by the framework.
        $this->app = $app = $application ?? $this->appFactory->createApplication(
            \array_merge(
                $initialInstances,
                [Client::class => $this->client],
            ),
        );

        $this->dispatchEvent($app, new WorkerStarting($app));
    }
}
