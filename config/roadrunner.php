<?php

declare(strict_types=1);

use Spiral\RoadRunner\Environment\Mode;
use Spiral\RoadRunnerLaravel\Grpc\GrpcWorker;
use Spiral\RoadRunnerLaravel\Http\HttpWorker;
use Spiral\RoadRunnerLaravel\Queue\QueueWorker;
use Spiral\RoadRunnerLaravel\Temporal\TemporalWorker;
use Temporal\Worker\WorkerFactoryInterface as TemporalWorkerFactoryInterface;

return [
    'cache' => [
        'storage' => 'cache',
    ],

    'grpc' => [
        'services' => [
            // GreeterInterface::class => new Greeter::class,
        ],
        'clients' => [
            'interceptors' => [
                // LoggingInterceptor::class,
            ],
            'services' => [
                // [
                //     'connection' => 'my-grpc-server:9002',
                //     'interfaces' => [
                //         GreeterInterface::class,
                //     ],
                // ],
                // [
                //     'connection' => 'my-secure-grpc-server:9002',
                //     'interfaces' => [
                //         GreeterInterface::class,
                //     ],
                //     'tls' => [
                //         'rootCerts' => '/path/to/ca.pem',
                //         'privateKey' => '/path/to/client.key',
                //         'certChain'  => '/path/to/client.crt',
                //         'serverName' => 'my.grpc.server',
                //     ],
                // ],
            ],
        ],
    ],

    'temporal' => [
        'address' => env('TEMPORAL_ADDRESS', '127.0.0.1:7233'),
        'defaultWorker' => env('TEMPORAL_TASK_QUEUE', TemporalWorkerFactoryInterface::DEFAULT_TASK_QUEUE),
        'workers' => [],
        'declarations' => [
            // 'App\Temporal\GreeterWorkflow'
        ],
    ],

    'workers' => [
        Mode::MODE_HTTP => HttpWorker::class,
        Mode::MODE_JOBS => QueueWorker::class,
        Mode::MODE_GRPC => GrpcWorker::class,
        Mode::MODE_TEMPORAL => TemporalWorker::class,
    ],
];
