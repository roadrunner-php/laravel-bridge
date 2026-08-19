<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel\Tests\Unit\Queue;

use PHPUnit\Framework\TestCase;
use RoadRunner\Jobs\DTO\V1\Job as JobProto;
use RoadRunner\Jobs\DTO\V1\PushRequest;
use RoadRunner\Jobs\DTO\V1\Stat;
use RoadRunner\Jobs\DTO\V1\Stats;
use Spiral\Goridge\RPC\RPCInterface;
use Spiral\RoadRunner\Jobs\Jobs;
use Spiral\RoadRunnerLaravel\Queue\RoadRunnerQueue;

final class RoadRunnerQueueTest extends TestCase
{
    public function test_queue_sizes_are_read_from_pipeline_stats(): void
    {
        $stats = new Stats([
            'stats' => [
                new Stat([
                    'pipeline' => 'default',
                    'active' => 5,
                    'delayed' => 3,
                    'reserved' => 2,
                ]),
                new Stat([
                    'pipeline' => 'secondary',
                    'active' => 7,
                ]),
            ],
        ]);

        $rpc = $this->createMock(RPCInterface::class);
        $rpc->method('withCodec')->willReturnSelf();
        $rpc->expects(self::exactly(5))
            ->method('call')
            ->with('jobs.Stat', self::isInstanceOf(Stats::class), Stats::class)
            ->willReturn($stats);

        $queue = new RoadRunnerQueue(new Jobs($rpc), $rpc);

        self::assertSame(10, $queue->size());
        self::assertSame(5, $queue->pendingSize());
        self::assertSame(3, $queue->delayedSize());
        self::assertSame(2, $queue->reservedSize());
        self::assertSame(7, $queue->pendingSize('secondary'));
        self::assertNull($queue->creationTimeOfOldestPendingJob());
    }

    public function test_resolve_task_name_uses_display_name_from_json_payload(): void
    {
        // Reproduces the exact wire shape Queue::createPayload() produces:
        // a JSON object whose top-level `displayName` field holds the job
        // class. The previous implementation read byte 0 of this string and
        // silently used `{` as the task name.
        $payload = '{"uuid":"abc","displayName":"App\\\\Jobs\\\\SendEmail","job":"Illuminate\\\\Queue\\\\CallQueuedHandler@call","data":{"commandName":"App\\\\Jobs\\\\SendEmail"}}';

        $name = $this->invokeResolveTaskName($payload);

        self::assertSame('App\\Jobs\\SendEmail', $name);
    }

    public function test_resolve_task_name_falls_back_to_uuid_when_payload_lacks_display_name(): void
    {
        $payload = '{"job":"X","data":{"foo":"bar"}}';

        $name = $this->invokeResolveTaskName($payload);

        self::assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/',
            $name,
            'Expected a v4 UUID fallback when payload has no displayName.',
        );
    }

    public function test_resolve_task_name_falls_back_to_uuid_for_empty_display_name(): void
    {
        // RoadRunner's QueueInterface::create() is typed `non-empty-string`
        // for the task name. An empty `displayName` in the JSON would slip
        // through is_string() and produce a name that fails downstream.
        $payload = '{"displayName":"","job":"X","data":{}}';

        $name = $this->invokeResolveTaskName($payload);

        self::assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/',
            $name,
            'Empty displayName must fall back to UUID, not produce an empty task name.',
        );
    }

    public function test_resolve_task_name_falls_back_to_uuid_for_non_json_payload(): void
    {
        // A bare string that isn't JSON at all — must not crash, must not
        // emit a string-offset warning, must produce a usable task name.
        $name = $this->invokeResolveTaskName('not-json-just-bytes');

        self::assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/',
            $name,
        );
    }

    public function test_push_raw_pushes_with_display_name_and_verbatim_payload(): void
    {
        $payload = '{"displayName":"App\\\\Jobs\\\\Foo","job":"X","data":{}}';
        $captured = null;

        $rpc = $this->buildRpcMock(
            pipelineName: 'q',
            capturePushedJob: static function (JobProto $job) use (&$captured): void {
                $captured = $job;
            },
        );

        $bridge = new RoadRunnerQueue(new Jobs($rpc), $rpc, 'q');
        $bridge->pushRaw($payload, 'q');

        self::assertInstanceOf(JobProto::class, $captured);
        self::assertSame('App\\Jobs\\Foo', $captured->getJob(), 'Task name should come from the payload `displayName`.');
        self::assertSame($payload, $captured->getPayload(), 'Payload must travel byte-for-byte to RoadRunner.');
    }

    public function test_later_raw_accepts_string_payload_and_applies_delay(): void
    {
        // The bug under repair: laterRaw was typed `array $payload` but
        // Queue::enqueueUsing() hands the closure a JSON string. The fix
        // widens the path to strings. This test exercises the full
        // pushed-to-RPC chain with a string payload and an integer delay.
        $payload = '{"displayName":"App\\\\Jobs\\\\Delayed","job":"X","data":{}}';
        $captured = null;

        $rpc = $this->buildRpcMock(
            pipelineName: 'q',
            capturePushedJob: static function (JobProto $job) use (&$captured): void {
                $captured = $job;
            },
        );

        $bridge = new RoadRunnerQueue(new Jobs($rpc), $rpc, 'q');

        $method = new \ReflectionMethod(RoadRunnerQueue::class, 'laterRaw');
        $method->invoke($bridge, 60, $payload, 'q', []);

        self::assertInstanceOf(JobProto::class, $captured);
        self::assertSame('App\\Jobs\\Delayed', $captured->getJob());
        self::assertSame($payload, $captured->getPayload());

        $options = $captured->getOptions();
        self::assertNotNull($options, 'Pushed Job must carry an Options proto.');
        self::assertSame(60, $options->getDelay(), 'withDelay() must propagate to the protobuf Options.');
    }

    public function test_available_at_returns_int_for_date_time_interface_delay(): void
    {
        // The companion bug to laterRaw's TypeError: Carbon 3 (Laravel 12)
        // returns float from diffInSeconds(), so the prior implementation
        // tripped a return-type TypeError on every Queue::later(DateTime).
        // The fix uses plain timestamp arithmetic; the return MUST be int.
        $queue = (new \ReflectionClass(RoadRunnerQueue::class))->newInstanceWithoutConstructor();
        $method = new \ReflectionMethod(RoadRunnerQueue::class, 'availableAt');

        $future = (new \DateTimeImmutable('+90 seconds'));
        $result = $method->invoke($queue, $future);

        self::assertIsInt($result);
        // Allow a small wall-clock slack so the test isn't flaky under load.
        self::assertGreaterThanOrEqual(85, $result);
        self::assertLessThanOrEqual(91, $result);
    }

    public function test_available_at_clamps_past_delay_to_zero(): void
    {
        // Carbon::diffInSeconds() in v3 is signed by default; a delay in
        // the past produced a negative float, which RoadRunner's
        // withDelay(int<0, max>) rejects. Plain max(0, ...) clamps it.
        $queue = (new \ReflectionClass(RoadRunnerQueue::class))->newInstanceWithoutConstructor();
        $method = new \ReflectionMethod(RoadRunnerQueue::class, 'availableAt');

        $past = (new \DateTimeImmutable('-30 seconds'));

        self::assertSame(0, $method->invoke($queue, $past));
    }

    public function test_available_at_passes_int_delay_through(): void
    {
        $queue = (new \ReflectionClass(RoadRunnerQueue::class))->newInstanceWithoutConstructor();
        $method = new \ReflectionMethod(RoadRunnerQueue::class, 'availableAt');

        self::assertSame(60, $method->invoke($queue, 60));
    }

    public function test_later_raw_parameter_type_admits_string(): void
    {
        // Regression guard for the original TypeError: ensure the typehint
        // on $payload is wide enough to accept the JSON string that
        // Queue::enqueueUsing() actually passes. If a future change retypes
        // this to `array` again, the bug returns silently for users until
        // their first ->delay() dispatch; this test catches it in CI.
        $param = (new \ReflectionMethod(RoadRunnerQueue::class, 'laterRaw'))->getParameters()[1];
        $type = (string) $param->getType();

        self::assertStringContainsString(
            'string',
            $type,
            'laterRaw $payload must accept string (what Queue::enqueueUsing actually delivers).',
        );
    }

    private function invokeResolveTaskName(string $payload): string
    {
        $queue = (new \ReflectionClass(RoadRunnerQueue::class))->newInstanceWithoutConstructor();
        $method = new \ReflectionMethod(RoadRunnerQueue::class, 'resolveTaskName');
        $result = $method->invoke($queue, $payload);

        self::assertIsString($result);

        return $result;
    }

    /**
     * Build an RPCInterface mock that:
     *   - returns a Stats showing the given pipeline as ready (so getQueue
     *     skips the resume() RPC),
     *   - captures any jobs.Push call so the test can assert on the Job proto.
     */
    private function buildRpcMock(string $pipelineName, ?callable $capturePushedJob): RPCInterface
    {
        $rpc = $this->createMock(RPCInterface::class);
        $rpc->method('withCodec')->willReturnSelf();

        $rpc->method('call')->willReturnCallback(
            static function (string $method, $payload) use ($pipelineName, $capturePushedJob) {
                if ($method === 'jobs.Stat') {
                    $stat = new Stat();
                    $stat->setPipeline($pipelineName);
                    $stat->setReady(true);

                    $stats = new Stats();
                    $stats->setStats([$stat]);

                    return $stats;
                }

                if ($method === 'jobs.Push' && $capturePushedJob !== null) {
                    \assert($payload instanceof PushRequest);
                    $capturePushedJob($payload->getJob());
                }

                return null;
            },
        );

        return $rpc;
    }
}
