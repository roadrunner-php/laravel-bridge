<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel;

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Laravel\Octane\Contracts\Client;
use Laravel\Octane\OctaneResponse;
use Laravel\Octane\RequestContext;

final readonly class DummyClient implements Client
{
    public function marshalRequest(RequestContext $context): array
    {
        throw new \BadMethodCallException('Cannot marshal request for queue client');
    }

    public function respond(RequestContext $context, OctaneResponse $response): void
    {
        throw new \BadMethodCallException('Cannot respond for queue client');
    }

    public function error(\Throwable $e, Application $app, Request $request, RequestContext $context): void
    {
        // TODO: Implement error() method.
    }
}
