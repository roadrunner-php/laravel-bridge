<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel\Tests\Unit\Queue;

use Illuminate\Contracts\Foundation\Application;
use PHPUnit\Framework\TestCase;
use Spiral\RoadRunner\Jobs\Task\ReceivedTaskInterface;
use Spiral\RoadRunnerLaravel\Queue\RoadRunnerJob;

final class RoadRunnerJobTest extends TestCase
{
    public function test_get_raw_body_returns_the_wire_payload_string_verbatim(): void
    {
        // Deliberately use non-canonical JSON (spaces after `:` and `,`) so a
        // regression that round-trips through json_decode + json_encode would
        // produce different bytes and fail the byte-identity assertion below.
        $wirePayload = '{"job": "Illuminate\\\\Queue\\\\CallQueuedHandler@call", "data": {"commandName": "App\\\\Jobs\\\\Demo"}}';

        $task = $this->createMock(ReceivedTaskInterface::class);
        $task->method('getPayload')->willReturn($wirePayload);

        $job = new RoadRunnerJob($this->createMock(Application::class), $task);

        $body = $job->getRawBody();

        self::assertIsString(
            $body,
            'getRawBody() is part of the Illuminate\\Contracts\\Queue\\Job contract and MUST return a string.',
        );
        self::assertSame(
            $wirePayload,
            $body,
            'getRawBody() should return the raw bytes the task carried on the wire, not a re-encoded version.',
        );
    }

    public function test_get_raw_body_is_consistent_with_decoded_payload(): void
    {
        // Sanity-check that getRawBody() and payload() agree on the data they
        // describe — they're two views of the same body (string vs decoded
        // array), and any divergence here would mean failure handlers
        // (FailingJob), tracing integrations, and retry serialization all see
        // different pictures of the same job.
        $wirePayload = '{"job":"X","data":{"foo":"bar"},"attempts":0}';

        $task = $this->createMock(ReceivedTaskInterface::class);
        $task->method('getPayload')->willReturn($wirePayload);

        $job = new RoadRunnerJob($this->createMock(Application::class), $task);

        self::assertSame($wirePayload, $job->getRawBody());
        self::assertSame(
            \json_decode($job->getRawBody(), true),
            $job->payload(),
        );
    }
}
