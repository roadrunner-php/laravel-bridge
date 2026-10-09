<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel\Tests\Unit\Queue;

use Testo\Test;
use Testo\Assert;
use Illuminate\Contracts\Foundation\Application;
use Spiral\RoadRunner\Jobs\Task\ReceivedTaskInterface;
use Spiral\RoadRunnerLaravel\Queue\RoadRunnerJob;

#[Test]
final class RoadRunnerJobTest
{
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
}
