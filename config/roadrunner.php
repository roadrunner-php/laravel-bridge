<?php

use Spiral\RoadRunnerLaravel\Events;
use Spiral\RoadRunnerLaravel\Defaults;
use Spiral\RoadRunnerLaravel\Listeners;
use Spiral\RoadRunner\Environment\Mode;

return [
    /*
    |--------------------------------------------------------------------------
    | Force HTTPS Schema Usage
    |--------------------------------------------------------------------------
    |
    | Set this value to `true` if your application uses HTTPS (required for
    | correct links generation, for example).
    |
    */

    'force_https' => (bool) env('APP_FORCE_HTTPS', false),

    /*
    |--------------------------------------------------------------------------
    | Event Listeners
    |--------------------------------------------------------------------------
    |
    | Worker provided by this package allows to interacts with request
    | processing loop using application events.
    |
    | Feel free to add your own event listeners.
    |
    */

    'listeners' => [
        Events\BeforeLoopStartedEvent::class => [
            ...Defaults::beforeLoopStarted(),
        ],

        Events\BeforeLoopIterationEvent::class => [
            ...Defaults::beforeLoopIteration(),
        ],

        Events\BeforeRequestHandlingEvent::class => [
            ...Defaults::beforeRequestHandling(),
            Listeners\InjectStatsIntoRequestListener::class,
        ],

        Events\AfterRequestHandlingEvent::class => [
            ...Defaults::afterRequestHandling(),
        ],

        Events\AfterLoopIterationEvent::class => [
            ...Defaults::afterLoopIteration(),
            Listeners\RunGarbageCollectorListener::class, // keep the memory usage low
            // Listeners\CleanupUploadedFilesListener::class, // remove temporary files
        ],

        Events\AfterLoopStoppedEvent::class => [
            ...Defaults::afterLoopStopped(),
        ],

        Events\LoopErrorOccurredEvent::class => [
            ...Defaults::loopErrorOccurred(),
            Listeners\SendExceptionToStderrListener::class,
            Listeners\StopWorkerListener::class,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Containers Pre Resolving / Clearing
    |--------------------------------------------------------------------------
    |
    | The bindings listed below will be resolved before the events loop
    | starting. Clearing a binding will force the container to resolve that
    | binding again when asked.
    |
    | Feel free to add your own bindings here.
    |
    */

    'warm' => [
        ...Defaults::servicesToWarm(),
    ],

    'clear' => [
        ...Defaults::servicesToClear(),
        'auth', // is not required for Laravel >= v8.35
    ],

    /*
    |--------------------------------------------------------------------------
    | Reset Providers
    |--------------------------------------------------------------------------
    |
    | Providers that will be registered on every request.
    |
    | Feel free to add your service-providers here.
    |
    */

    'reset_providers' => [
        ...Defaults::providersToReset(),
        Illuminate\Auth\AuthServiceProvider::class,             // is not required for Laravel >= v8.35
        Illuminate\Pagination\PaginationServiceProvider::class, // is not required for Laravel >= v8.35
    ],

    /*
    |--------------------------------------------------------------------------
    | Worker Classes
    |--------------------------------------------------------------------------
    |
    | Here you can override the worker class for processing different kinds of
    | jobs, that received from the RoadRunner daemon. The key is a worker mode.
    |
    */

    'workers' => [
        Mode::MODE_HTTP => \Spiral\RoadRunnerLaravel\Worker::class,
        // Mode::MODE_JOBS => ...,
        // Mode::MODE_TEMPORAL => ...,
    ],
];
