<?php

use Spiral\RoadRunnerLaravel\Events;
use Spiral\RoadRunnerLaravel\Listeners;
use Spiral\RoadRunnerLaravel\Listeners\BuiltIn as BuiltInListeners;

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
            ...BuiltInListeners::beforeLoopStarted(),
        ],

        Events\BeforeLoopIterationEvent::class => [
            ...BuiltInListeners::beforeLoopIteration(),
            // Listeners\ResetLaravelScoutListener::class,     // for <https://github.com/laravel/scout>
            // Listeners\ResetLaravelSocialiteListener::class, // for <https://github.com/laravel/socialite>
            // Listeners\ResetInertiaListener::class,          // for <https://github.com/inertiajs/inertia-laravel>
        ],

        Events\BeforeRequestHandlingEvent::class => [
            ...BuiltInListeners::beforeRequestHandling(),
        ],

        Events\AfterRequestHandlingEvent::class => [
            ...BuiltInListeners::afterRequestHandling(),
        ],

        Events\AfterLoopIterationEvent::class => [
            ...BuiltInListeners::afterLoopIteration(),
            Listeners\RunGarbageCollectorListener::class, // keep the memory usage low
        ],

        Events\AfterLoopStoppedEvent::class => [
            ...BuiltInListeners::afterLoopStopped(),
        ],

        Events\LoopErrorOccurredEvent::class => [
            ...BuiltInListeners::loopErrorOccurred(),
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
    | starting (see `WarmContainersListener` sources). Clearing a binding
    | will force the container to resolve that binding again when asked (see
    | `ClearInstancesListener` sources).
    |
    */

    'pre_resolving' => [
        'auth',
        'cache',
        'cache.store',
        'config',
        'cookie',
        'db',
        'db.factory',
        'encrypter',
        'files',
        'hash',
        'log',
        'router',
        'routes',
        'session',
        'session.store',
        'translator',
        'url',
        'view',
    ],

    'clear_instances' => [
        'auth', // is not required for Laravel >= v8.35
    ],

    /*
    |--------------------------------------------------------------------------
    | Reset Providers
    |--------------------------------------------------------------------------
    |
    | Providers that will be registered on every request (see
    | `ResetProvidersListener` sources).
    |
    */

    'reset_providers' => [
        Illuminate\Auth\AuthServiceProvider::class, // is not required for Laravel >= v8.35
        // App\Your\Custom\AuthServiceProvider::class,
        Illuminate\Pagination\PaginationServiceProvider::class, // is not required for Laravel >= v8.35
    ],
];
