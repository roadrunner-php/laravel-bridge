<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel\Tests\Unit\Queue;

use Illuminate\Container\Container;
use Illuminate\Database\DatabaseTransactionsManager;
use RoadRunner\Jobs\DTO\V1\Job as JobProto;
use RoadRunner\Jobs\DTO\V1\PushRequest;
use RoadRunner\Jobs\DTO\V1\Stat;
use RoadRunner\Jobs\DTO\V1\Stats;
use Spiral\Goridge\RPC\RPCInterface;
use Spiral\RoadRunner\Jobs\Jobs;
use Spiral\RoadRunner\Jobs\Queue\Driver;
use Spiral\RoadRunnerLaravel\Queue\RoadRunnerQueue;
use Spiral\RoadRunnerLaravel\Tests\Unit\Queue\Fixture\PrioritizedJob;
use Testo\Assert;
use Testo\Data\DataProvider;
use Testo\Data\DataSet;
use Testo\Expect;
use Testo\Test;

#[Test]
final class RoadRunnerQueueTest
{
    /**
     * @return array<string, array{int|null}>
     */
    public static function dispatchMethods(): array
    {
        return [
            'push' => [null],
            'later' => [60],
        ];
    }

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

        $rpc = \Mockery::mock(RPCInterface::class)->shouldIgnoreMissing();
        $rpc->shouldReceive('withCodec')->andReturnSelf();
        $rpc->shouldReceive('call')->times(5)->with('jobs.Stat', \Mockery::type(Stats::class), Stats::class, \Mockery::andAnyOtherArgs())->andReturn($stats);

        $queue = new RoadRunnerQueue(new Jobs($rpc), $rpc);

        Assert::same($queue->size(), 10);
        Assert::same($queue->pendingSize(), 5);
        Assert::same($queue->delayedSize(), 3);
        Assert::same($queue->reservedSize(), 2);
        Assert::same($queue->pendingSize('secondary'), 7);
        Assert::null($queue->creationTimeOfOldestPendingJob());
    }

    #[DataProvider('dispatchMethods')]
    public function test_dispatch_after_commit_waits_for_transaction(?int $delay): void
    {
        $pushed = [];
        $rpc = $this->buildRpcMock('q', static function (JobProto $job) use (&$pushed): void {
            $pushed[] = $job;
        });

        $transactions = new DatabaseTransactionsManager();
        $container = new Container();
        $container->instance('db.transactions', $transactions);

        $queue = new RoadRunnerQueue(new Jobs($rpc), $rpc, 'q');
        $queue->setContainer($container);

        $job = new \stdClass();
        $job->afterCommit = true;

        $transactions->begin('default', 1);

        $result = $delay === null ? $queue->push($job) : $queue->later($delay, $job);

        Assert::null($result);
        Assert::same($pushed, []);

        $transactions->commit('default', 1, 0);

        Assert::count($pushed, 1);
        Assert::same($pushed[0]->getOptions()->getDelay(), $delay ?? 0);
    }

    #[DataProvider('dispatchMethods')]
    public function test_dispatch_after_commit_drops_job_on_rollback(?int $delay): void
    {
        $pushed = [];
        $rpc = $this->buildRpcMock('q', static function (JobProto $job) use (&$pushed): void {
            $pushed[] = $job;
        });

        $transactions = new DatabaseTransactionsManager();
        $container = new Container();
        $container->instance('db.transactions', $transactions);

        $queue = new RoadRunnerQueue(new Jobs($rpc), $rpc, 'q');
        $queue->setContainer($container);

        $job = new \stdClass();
        $job->afterCommit = true;

        $transactions->begin('default', 1);

        $result = $delay === null ? $queue->push($job) : $queue->later($delay, $job);

        Assert::null($result);

        $transactions->rollback('default', 0);

        Assert::same($pushed, []);
    }

    #[DataProvider('dispatchMethods')]
    public function test_dispatch_after_commit_without_transaction_sends_immediately(?int $delay): void
    {
        $pushed = [];
        $rpc = $this->buildRpcMock('q', static function (JobProto $job) use (&$pushed): void {
            $pushed[] = $job;
        });

        $container = new Container();
        $container->instance('db.transactions', new DatabaseTransactionsManager());

        $queue = new RoadRunnerQueue(new Jobs($rpc), $rpc, 'q');
        $queue->setContainer($container);

        $job = new \stdClass();
        $job->afterCommit = true;

        $result = $delay === null ? $queue->push($job) : $queue->later($delay, $job);

        Assert::null($result);
        Assert::count($pushed, 1);
        Assert::same($pushed[0]->getOptions()->getDelay(), $delay ?? 0);
    }

    #[DataProvider('dispatchMethods')]
    public function test_dispatch_before_commit_returns_task_id(?int $delay): void
    {
        $pushed = [];
        $rpc = $this->buildRpcMock('q', static function (JobProto $job) use (&$pushed): void {
            $pushed[] = $job;
        });

        $container = new Container();
        $container->instance('db.transactions', new DatabaseTransactionsManager());

        $queue = new RoadRunnerQueue(new Jobs($rpc), $rpc, 'q');
        $queue->setContainer($container);

        $job = new \stdClass();
        $job->afterCommit = false;

        $result = $delay === null ? $queue->push($job) : $queue->later($delay, $job);

        Assert::count($pushed, 1);
        Assert::same($result, $pushed[0]->getId());
    }

    public function test_resolve_task_name_uses_display_name_from_json_payload(): void
    {
        // Reproduces the exact wire shape Queue::createPayload() produces:
        // a JSON object whose top-level `displayName` field holds the job
        // class. The previous implementation read byte 0 of this string and
        // silently used `{` as the task name.
        $payload = '{"uuid":"abc","displayName":"App\\\\Jobs\\\\SendEmail","job":"Illuminate\\\\Queue\\\\CallQueuedHandler@call","data":{"commandName":"App\\\\Jobs\\\\SendEmail"}}';

        $name = $this->invokeResolveTaskName($payload);

        Assert::same($name, 'App\\Jobs\\SendEmail');
    }

    public function test_resolve_task_name_falls_back_to_uuid_when_payload_lacks_display_name(): void
    {
        $payload = '{"job":"X","data":{"foo":"bar"}}';

        $name = $this->invokeResolveTaskName($payload);

        Assert::string($name)->matchesRegex('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/', 'Expected a v4 UUID fallback when payload has no displayName.');
    }

    public function test_resolve_task_name_falls_back_to_uuid_for_empty_display_name(): void
    {
        // RoadRunner's QueueInterface::create() is typed `non-empty-string`
        // for the task name. An empty `displayName` in the JSON would slip
        // through is_string() and produce a name that fails downstream.
        $payload = '{"displayName":"","job":"X","data":{}}';

        $name = $this->invokeResolveTaskName($payload);

        Assert::string($name)->matchesRegex('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/', 'Empty displayName must fall back to UUID, not produce an empty task name.');
    }

    public function test_resolve_task_name_falls_back_to_uuid_for_non_json_payload(): void
    {
        // A bare string that isn't JSON at all — must not crash, must not
        // emit a string-offset warning, must produce a usable task name.
        $name = $this->invokeResolveTaskName('not-json-just-bytes');

        Assert::string($name)->matchesRegex('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/');
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

        Assert::instanceOf($captured, JobProto::class);
        Assert::same($captured->getJob(), 'App\\Jobs\\Foo', 'Task name should come from the payload `displayName`.');
        Assert::same($captured->getPayload(), $payload, 'Payload must travel byte-for-byte to RoadRunner.');
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

        Assert::instanceOf($captured, JobProto::class);
        Assert::same($captured->getJob(), 'App\\Jobs\\Delayed');
        Assert::same($captured->getPayload(), $payload);

        $options = $captured->getOptions();
        Assert::notNull($options, 'Pushed Job must carry an Options proto.');
        Assert::same($options->getDelay(), 60, 'withDelay() must propagate to the protobuf Options.');
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

        Assert::int($result);
        // Allow a small wall-clock slack so the test isn't flaky under load.
        Assert::numeric($result)->greaterThanOrEqual(85)->lessThanOrEqual(91);
    }

    public function test_available_at_clamps_past_delay_to_zero(): void
    {
        // Carbon::diffInSeconds() in v3 is signed by default; a delay in
        // the past produced a negative float, which RoadRunner's
        // withDelay(int<0, max>) rejects. Plain max(0, ...) clamps it.
        $queue = (new \ReflectionClass(RoadRunnerQueue::class))->newInstanceWithoutConstructor();
        $method = new \ReflectionMethod(RoadRunnerQueue::class, 'availableAt');

        $past = (new \DateTimeImmutable('-30 seconds'));

        Assert::same($method->invoke($queue, $past), 0);
    }

    public function test_available_at_passes_int_delay_through(): void
    {
        $queue = (new \ReflectionClass(RoadRunnerQueue::class))->newInstanceWithoutConstructor();
        $method = new \ReflectionMethod(RoadRunnerQueue::class, 'availableAt');

        Assert::same($method->invoke($queue, 60), 60);
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

        Assert::string($type)->contains('string', 'laterRaw $payload must accept string (what Queue::enqueueUsing actually delivers).');
    }

    public function test_pop_is_not_supported(): never
    {
        $rpc = \Mockery::mock(RPCInterface::class);
        $rpc->shouldReceive('withCodec')->andReturnSelf();

        Expect::exception(\BadMethodCallException::class)->withMessage('Pop is not supported');

        (new RoadRunnerQueue(new Jobs($rpc), $rpc))->pop();
    }

    public function test_sizes_are_zero_for_an_unknown_pipeline(): void
    {
        $rpc = \Mockery::mock(RPCInterface::class);
        $rpc->shouldReceive('withCodec')->andReturnSelf();
        $rpc->shouldReceive('call')
            ->with('jobs.Stat', \Mockery::type(Stats::class), Stats::class)
            ->andReturn(new Stats(['stats' => [new Stat(['pipeline' => 'other', 'active' => 9])]]));

        $queue = new RoadRunnerQueue(new Jobs($rpc), $rpc);

        Assert::same($queue->size('missing'), 0);
        Assert::same($queue->pendingSize('missing'), 0);
    }

    public function test_push_resumes_a_paused_pipeline(): void
    {
        $calls = [];
        $rpc = \Mockery::mock(RPCInterface::class);
        $rpc->shouldReceive('withCodec')->andReturnSelf();
        $rpc->shouldReceive('call')->andReturnUsing(
            static function (string $method, mixed $payload) use (&$calls): ?Stats {
                $calls[] = $method;

                return $method === 'jobs.Stat'
                    ? new Stats(['stats' => [new Stat(['pipeline' => 'q', 'ready' => false])]])
                    : null;
            },
        );

        (new RoadRunnerQueue(new Jobs($rpc), $rpc, 'q'))->pushRaw('{"displayName":"Foo"}');

        Assert::same($calls, ['jobs.Stat', 'jobs.Resume', 'jobs.Push']);
    }

    public function test_push_raw_applies_default_queue_options(): void
    {
        $captured = null;
        $rpc = $this->buildRpcMock('q', static function (JobProto $job) use (&$captured): void {
            $captured = $job;
        });

        $queue = new RoadRunnerQueue(new Jobs($rpc), $rpc, 'q', ['priority' => 5, 'delay' => 7, 'auto_ack' => true]);
        $queue->pushRaw('{"displayName":"Foo"}');

        Assert::instanceOf($captured, JobProto::class);
        Assert::same($captured->getOptions()->getPriority(), 5);
        Assert::same($captured->getOptions()->getDelay(), 7);
        Assert::true($captured->getOptions()->getAutoAck());
        Assert::same($captured->getOptions()->getPipeline(), 'q');
    }

    public function test_push_raw_sets_the_topic_for_kafka_pipelines(): void
    {
        $captured = null;
        $rpc = $this->buildRpcMock('q', static function (JobProto $job) use (&$captured): void {
            $captured = $job;
        });

        $queue = new RoadRunnerQueue(new Jobs($rpc), $rpc, 'q', ['driver' => Driver::Kafka, 'topic' => 'events']);
        $queue->pushRaw('{"displayName":"Foo"}');

        Assert::instanceOf($captured, JobProto::class);
        Assert::same($captured->getOptions()->getTopic(), 'events');
    }

    public function test_push_uses_options_declared_by_the_job(): void
    {
        $captured = null;
        $rpc = $this->buildRpcMock('q', static function (JobProto $job) use (&$captured): void {
            $captured = $job;
        });

        $container = new Container();
        $container->instance('db.transactions', new DatabaseTransactionsManager());

        $queue = new RoadRunnerQueue(new Jobs($rpc), $rpc, 'q', ['priority' => 5]);
        $queue->setContainer($container);
        $queue->push(new PrioritizedJob());

        Assert::instanceOf($captured, JobProto::class);
        Assert::same($captured->getOptions()->getPriority(), 42);
        Assert::same($captured->getOptions()->getDelay(), 3);
    }

    public function test_push_raw_connects_to_the_given_queue_instead_of_the_default(): void
    {
        $captured = null;
        $rpc = $this->buildRpcMock('other', static function (JobProto $job) use (&$captured): void {
            $captured = $job;
        });

        (new RoadRunnerQueue(new Jobs($rpc), $rpc, 'q'))->pushRaw('{"displayName":"Foo"}', 'other');

        Assert::instanceOf($captured, JobProto::class);
        Assert::same($captured->getOptions()->getPipeline(), 'other');
    }

    public function test_push_raw_rejects_an_empty_queue_name(): never
    {
        $rpc = $this->buildRpcMock('q', null);

        Expect::exception(\InvalidArgumentException::class)->withMessage('The queue name must not be empty.');

        (new RoadRunnerQueue(new Jobs($rpc), $rpc, 'q'))->pushRaw('{"displayName":"Foo"}', '');
    }

    #[DataSet([['auto_ack' => 'yes'], 'auto_ack'], 'auto_ack is not a boolean')]
    #[DataSet([['delay' => -1], 'delay'], 'negative delay')]
    #[DataSet([['priority' => '5'], 'priority'], 'priority is a string')]
    #[DataSet([['driver' => Driver::Kafka], 'topic'], 'Kafka without a topic')]
    #[DataSet([['driver' => Driver::Kafka, 'topic' => ''], 'topic'], 'Kafka with an empty topic')]
    #[DataSet([['driver' => Driver::Kafka, 'topic' => 5], 'topic'], 'Kafka with a non-string topic')]
    public function test_invalid_queue_options_are_rejected(array $options, string $option): never
    {
        $rpc = $this->buildRpcMock('q', null);

        Expect::exception(\InvalidArgumentException::class)->withMessageContaining("`{$option}`");

        (new RoadRunnerQueue(new Jobs($rpc), $rpc, 'q', $options))->pushRaw('{"displayName":"Foo"}');
    }

    public function test_unexpected_stats_response_is_rejected(): never
    {
        $rpc = \Mockery::mock(RPCInterface::class);
        $rpc->shouldReceive('withCodec')->andReturnSelf();
        $rpc->shouldReceive('call')->andReturn(null);

        Expect::exception(\UnexpectedValueException::class)->withMessageContaining('null');

        (new RoadRunnerQueue(new Jobs($rpc), $rpc, 'q'))->size();
    }

    private function invokeResolveTaskName(string $payload): string
    {
        $queue = (new \ReflectionClass(RoadRunnerQueue::class))->newInstanceWithoutConstructor();
        $method = new \ReflectionMethod(RoadRunnerQueue::class, 'resolveTaskName');
        $result = $method->invoke($queue, $payload);

        Assert::string($result);

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
        $rpc = \Mockery::mock(RPCInterface::class)->shouldIgnoreMissing();
        $rpc->shouldReceive('withCodec')->andReturnSelf();

        $rpc->shouldReceive('call')->andReturnUsing(static function (string $method, $payload) use ($pipelineName, $capturePushedJob) {
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
        });

        return $rpc;
    }
}
