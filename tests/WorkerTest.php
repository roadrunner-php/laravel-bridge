<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel\Tests;

use Mockery as m;
use RuntimeException;
use Illuminate\Support\Str;
use Spiral\Goridge\StreamRelay;
use Spiral\RoadRunner\PSR7Client;
use Spiral\RoadRunnerLaravel\Events;
use Spiral\RoadRunnerLaravel\Worker;
use Psr\Http\Message\ResponseInterface;
use Spiral\RoadRunner\Worker as RRWorker;
use Psr\Http\Message\ServerRequestInterface;
use Spiral\RoadRunnerLaravel\ServiceProvider;
use Spiral\RoadRunnerLaravel\WorkerInterface;
use Illuminate\Contracts\Foundation\Application;
use Spiral\RoadRunner\Diactoros\ServerRequestFactory;
use Illuminate\Contracts\Http\Kernel as HttpKernelContract;

/**
 * Important note: when you catch mockery errors like `Method respond(<MultiArgumentClosure===true>) from ...`
 * probably it means failed asserts, not `respond` method calls count.
 *
 * @covers \Spiral\RoadRunnerLaravel\Worker<extended>
 */
class WorkerTest extends AbstractTestCase
{
    /**
     * @var ServerRequestFactory
     */
    protected $requests_factory;

    /**
     * @var RRWorker
     */
    protected $rr_worker;

    /**
     * @var resource
     */
    protected $out;

    /**
     * @var string
     */
    protected $base_dir = __DIR__ . '/../vendor/laravel/laravel';

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->out              = \fopen('php://memory', 'rb+');
        $this->requests_factory = new ServerRequestFactory();
        $this->rr_worker        = new RRWorker(new StreamRelay(\STDIN, $this->out));
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown(): void
    {
        \fclose($this->out);

        parent::tearDown();
    }

    /**
     * @return void
     */
    public function testImplementation(): void
    {
        $this->assertInstanceOf(WorkerInterface::class, new Worker($this->base_dir));
    }

    /**
     * @return void
     */
    public function testStartWithoutRefreshApp(): void
    {
        /** @var m\MockInterface|Worker $worker */
        $worker = m::mock(Worker::class, [$this->base_dir])
            ->makePartial()
            ->shouldAllowMockingProtectedMethods()
            ->expects('createPsr7Client')
            ->andReturn(
                m::mock(PSR7Client::class, [$this->rr_worker])
                    ->makePartial()
                    ->shouldReceive('acceptRequest')
                    ->twice()
                    ->andReturnUsing($this->getOnceRequestGenerationClosure())
                    ->getMock()
                    ->shouldReceive('respond')
                    ->once()
                    ->withArgs($this->getHtmlResponseValidationClosure())
                    ->getMock()
            )
            ->getMock()
            ->shouldReceive('preResolveApplicationAbstracts')
            ->withArgs(function (Application $app): bool {
                $this->getAfterBootstrapClosure()($app);

                $this->assertInstanceOf(PSR7Client::class, $client = $app->make(PSR7Client::class));
                $this->assertSame($client, $app->make(PSR7Client::class));

                return true;
            })
            ->passthru()
            ->getMock()
            ->shouldReceive('createApplication')
            ->once() // <-- important
            ->passthru()
            ->getMock();

        $worker->start(); // `false` must be by default
    }

    /**
     * @return void
     */
    public function testStartWithRefreshApp(): void
    {
        /** @var m\MockInterface|Worker $worker */
        $worker = m::mock(Worker::class, [$this->base_dir])
            ->makePartial()
            ->shouldAllowMockingProtectedMethods()
            ->expects('createPsr7Client')
            ->andReturn(
                m::mock(PSR7Client::class, [$this->rr_worker])
                    ->makePartial()
                    ->shouldReceive('acceptRequest')
                    ->twice()
                    ->andReturnUsing($this->getOnceRequestGenerationClosure())
                    ->getMock()
                    ->shouldReceive('respond')
                    ->once()
                    ->withArgs($this->getHtmlResponseValidationClosure())
                    ->getMock()
            )
            ->getMock()
            ->shouldReceive('preResolveApplicationAbstracts')
            ->withArgs(function (Application $app): bool {
                $this->getAfterBootstrapClosure()($app);

                $this->assertInstanceOf(PSR7Client::class, $client = $app->make(PSR7Client::class));
                $this->assertSame($client, $app->make(PSR7Client::class));

                return true;
            })
            ->passthru()
            ->getMock()
            ->shouldReceive('createApplication')
            ->twice() // <-- important
            ->passthru()
            ->getMock()
            ->shouldReceive('setApplicationInstance')
            ->twice()
            ->passthru()
            ->getMock();

        $worker->start(true);
    }

    /**
     * @return void
     */
    public function testWorkersEventFiring(): void
    {
        /** @var int[] $fired_events Key is event class, value - firing count */
        $fired_events = [];

        $expected_events = [
            Events\AfterLoopIterationEvent::class,
            Events\AfterLoopStoppedEvent::class,
            Events\AfterRequestHandlingEvent::class,
            Events\BeforeLoopIterationEvent::class,
            Events\BeforeLoopStartedEvent::class,
            Events\BeforeRequestHandlingEvent::class,
        ];

        /** @var m\MockInterface|Worker $worker */
        $worker = m::mock(Worker::class, [$this->base_dir])
            ->makePartial()
            ->shouldAllowMockingProtectedMethods()
            ->expects('createPsr7Client')
            ->andReturn(
                m::mock(PSR7Client::class, [$this->rr_worker])
                    ->makePartial()
                    ->shouldReceive('acceptRequest')
                    ->twice()
                    ->andReturnUsing($this->getOnceRequestGenerationClosure())
                    ->getMock()
                    ->shouldReceive('respond')
                    ->once()
                    ->withArgs($this->getHtmlResponseValidationClosure())
                    ->getMock()
            )
            ->getMock()
            ->shouldReceive('preResolveApplicationAbstracts')
            ->withArgs($this->getAfterBootstrapClosure())
            ->withArgs($this->getMockEventsClosure($fired_events))
            ->passthru()
            ->getMock();

        $worker->start();

        foreach ($expected_events as $expected_event) {
            if (!isset($fired_events[$expected_event])) {
                $this->fail("Event [{$expected_event}] was not fired");
            } else {
                $this->assertSame(
                    1,
                    $fired_events[$expected_event],
                    "Event [{$expected_event}] was fired {$fired_events[$expected_event]} times (instead once)"
                );
            }
        }
    }

    /**
     * @return void
     */
    public function testWorkerErrorHandling(): void
    {
        /** @var int[] $fired_events Key is event class, value - firing count */
        $fired_events = [];

        $expected_events = [
            Events\LoopErrorOccurredEvent::class,
        ];

        $psr_worker = (new PSR7Client($this->rr_worker))->getWorker();

        $mock_event_closure = $this->getMockEventsClosure($fired_events);

        /** @var m\MockInterface|Worker $worker */
        $worker = m::mock(Worker::class, [$this->base_dir])
            ->makePartial()
            ->shouldAllowMockingProtectedMethods()
            ->expects('createPsr7Client')
            ->andReturn(
                m::mock(PSR7Client::class, [$this->rr_worker])
                    ->makePartial()
                    ->shouldReceive('acceptRequest')
                    ->andReturnUsing($this->getOnceRequestGenerationClosure())
                    ->getMock()
                    ->shouldReceive('respond')
                    ->andThrow(new RuntimeException($exception_message = 'all is ok ' . Str::random()))
                    ->getMock()
                    ->shouldReceive('getWorker')
                    ->andReturn(
                        m::mock($psr_worker)
                            ->makePartial()
                            ->shouldReceive('error')
                            ->once()
                            ->withArgs(function ($error_text) use ($exception_message): bool {
                                $this->assertStringContainsString($exception_message, $error_text);

                                return true;
                            })
                            ->getMock()
                    )
                    ->getMock()
            )
            ->getMock()
            ->shouldReceive('preResolveApplicationAbstracts')
            ->withArgs(static function (Application $app) use ($mock_event_closure): bool {
                $mock_event_closure($app);

                $config = $app->make('config');

                $config->set('app.debug', true);

                return true;
            })
            ->passthru()
            ->getMock();

        $worker->start();

        foreach ($expected_events as $expected_event) {
            if (!isset($fired_events[$expected_event])) {
                $this->fail("Event [{$expected_event}] was not fired");
            } else {
                $this->assertSame(
                    1,
                    $fired_events[$expected_event],
                    "Event [{$expected_event}] was fired {$fired_events[$expected_event]} times (instead once)"
                );
            }
        }
    }

    /**
     * @return void
     */
    public function testWorkerErrorHandlingWithMaskedException(): void
    {
        /** @var int[] $fired_events Key is event class, value - firing count */
        $fired_events = [];

        $psr_worker = (new PSR7Client($this->rr_worker))->getWorker();

        /** @var m\MockInterface|Worker $worker */
        $worker = m::mock(Worker::class, [$this->base_dir])
            ->makePartial()
            ->shouldAllowMockingProtectedMethods()
            ->expects('createPsr7Client')
            ->andReturn(
                m::mock(PSR7Client::class, [$this->rr_worker])
                    ->makePartial()
                    ->shouldReceive('acceptRequest')
                    ->andReturnUsing($this->getOnceRequestGenerationClosure())
                    ->getMock()
                    ->shouldReceive('respond')
                    ->andThrow(new RuntimeException('all is ok ' . Str::random()))
                    ->getMock()
                    ->shouldReceive('getWorker')
                    ->andReturn(
                        m::mock($psr_worker)
                            ->makePartial()
                            ->shouldReceive('error')
                            ->once()
                            ->withArgs(function ($error_text): bool {
                                $this->assertStringContainsString('Internal server error', $error_text);

                                return true;
                            })
                            ->getMock()
                    )
                    ->getMock()
            )
            ->getMock()
            ->shouldReceive('isDebugModeEnabled')
            ->andReturnFalse()
            ->getMock()
            ->shouldReceive('preResolveApplicationAbstracts')
            ->withArgs($this->getMockEventsClosure($fired_events))
            ->passthru()
            ->getMock();

        $worker->start();
    }

    public function testWorkerErrorHandlingAfterRespond(): void
    {
        /** @var int[] $fired_events Key is event class, value - firing count */
        $fired_events = [];

        $expected_events = [
            Events\LoopErrorOccurredEvent::class,
        ];

        $event_closure = $this->getMockEventsClosure($fired_events);

        /** @var m\MockInterface|Worker $worker */
        $worker = m::mock(Worker::class, [$this->base_dir])
            ->makePartial()
            ->shouldAllowMockingProtectedMethods()
            ->expects('createPsr7Client')
            ->andReturn(
                m::mock(PSR7Client::class, [$this->rr_worker])
                    ->makePartial()
                    ->shouldReceive('acceptRequest')
                    ->twice()
                    ->andReturnUsing($this->getOnceRequestGenerationClosure())
                    ->getMock()
                    ->shouldReceive('respond')
                    ->once()
                    ->withArgs($this->getHtmlResponseValidationClosure())
                    ->getMock()
                    ->shouldReceive('getWorker')
                    ->andReturn(
                        m::mock()
                            ->shouldNotReceive('error') // <-- important
                            ->getMock()
                    )
                    ->getMock()
            )
            ->getMock()
            ->shouldReceive('preResolveApplicationAbstracts')
            ->withArgs(static function (Application $app) use ($event_closure): bool {
                $event_closure($app);
                $app->instance(HttpKernelContract::class, m::mock($app->make(HttpKernelContract::class))
                    ->makePartial()
                    ->shouldReceive('terminate')
                    ->once()
                    ->andThrow(new \RuntimeException('Muted exception'))
                    ->getMock());

                return true;
            })
            ->getMock();

        $worker->start();

        foreach ($expected_events as $expected_event) {
            if (!isset($fired_events[$expected_event])) {
                $this->fail("Event [{$expected_event}] was not fired");
            } else {
                $this->assertSame(
                    1,
                    $fired_events[$expected_event],
                    "Event [{$expected_event}] was fired {$fired_events[$expected_event]} times (instead once)"
                );
            }
        }
    }

    /**
     * @return void
     */
    public function test404errorResponse(): void
    {
        /** @var m\MockInterface|Worker $worker */
        $worker = m::mock(Worker::class, [$this->base_dir])
            ->makePartial()
            ->shouldAllowMockingProtectedMethods()
            ->expects('createPsr7Client')
            ->andReturn(
                m::mock(PSR7Client::class, [$this->rr_worker])
                    ->makePartial()
                    ->shouldReceive('acceptRequest')
                    ->twice()
                    ->andReturnUsing($this->getOnceRequestGenerationClosure('GET', Str::random()))
                    ->getMock()
                    ->shouldReceive('respond')
                    ->once()
                    ->withArgs(function (ResponseInterface $response): bool {
                        $this->assertSame(404, $response->getStatusCode());
                        $this->assertNotEmpty($response->getHeaders());
                        $this->assertStringContainsString('<html', Str::lower((string) $response->getBody()));

                        return true;
                    })
                    ->getMock()
            )
            ->getMock()
            ->shouldReceive('preResolveApplicationAbstracts')
            ->withArgs($this->getAfterBootstrapClosure())
            ->passthru()
            ->getMock();

        $worker->start();
    }

    /**
     * This closure should be executed when application was bootstrapped and before worker loop started.
     *
     * @return callable
     */
    private function getAfterBootstrapClosure(): callable
    {
        return static function (Application $app): bool {
            // Register package service-provider
            $app->register(ServiceProvider::class);

            return true;
        };
    }

    /**
     * This closure generates server request for worker loop only ONCE (any next calls will return null).
     *
     * @param string $method
     * @param string $uri
     * @param array  $server_params
     *
     * @return callable
     */
    private function getOnceRequestGenerationClosure(
        string $method = 'GET',
        string $uri = 'http://unit-test/',
        array $server_params = []
    ): callable {
        return function () use ($method, $uri, $server_params): ?ServerRequestInterface {
            // Send request into loop only once
            static $sent = false;

            if ($sent !== true) {
                $sent = true;

                return $this->requests_factory->createServerRequest($method, $uri, $server_params);
            }

            return null;
        };
    }

    /**
     * Basic closure for application kernel response validation.
     *
     * @return callable
     */
    private function getHtmlResponseValidationClosure(): callable
    {
        return function (ResponseInterface $response): bool {
            // Validate application response
            $this->assertSame(200, $response->getStatusCode());
            $this->assertNotEmpty($response->getHeaders());
            $this->assertStringContainsString('<html', Str::lower((string) $response->getBody()));

            return true;
        };
    }

    /**
     * Use this closure if you want to mute any fired events.
     *
     * @param $fired_events
     *
     * @return \Closure
     */
    private function getMockEventsClosure(&$fired_events): callable
    {
        return static function (Application $app) use (&$fired_events): bool {
            $app->instance(
                'events',
                m::mock($app->make('events'))
                    ->shouldReceive('dispatch')
                    ->withArgs(static function ($event) use (&$fired_events): bool {
                        $event_class = \is_object($event)
                            ? \get_class($event)
                            : (string) $event;

                        if (!isset($fired_events[$event_class])) {
                            $fired_events[$event_class] = 1;
                        } else {
                            $fired_events[$event_class]++;
                        }

                        return true;
                    })
                    ->getMock()
            );

            return true;
        };
    }
}
