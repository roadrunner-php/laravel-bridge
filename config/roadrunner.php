<?php

use Spiral\RoadRunnerLaravel\Events;
use Spiral\RoadRunnerLaravel\Listeners;

return [
    /*
    |--------------------------------------------------------------------------
    | Force HTTPS Schema Usage
    |--------------------------------------------------------------------------
    |
    | Set this value to `true` if your application uses HTTPS (required for
    | example for correct links generation).
    |
    */

    'force_https' => (bool) env('APP_FORCE_HTTPS', false),

    /*
    |--------------------------------------------------------------------------
    | Containers Pre Resolving
    |--------------------------------------------------------------------------
    |
    | Declared here abstractions will be resolved before events loop will be
    | started.
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

    /*
    |--------------------------------------------------------------------------
    | Event Listeners
    |--------------------------------------------------------------------------
    |
    | Worker provided by this package allows to interacts with request
    | processing loop using application events. Feel free to add your own event
    | listeners.
    |
    */

    'listeners' => [
        Events\BeforeLoopStartedEvent::class => [
            Listeners\FixSymfonyFileValidationListener::class,
        ],

        Events\BeforeLoopIterationEvent::class => [
            Listeners\EnableHttpMethodParameterOverrideListener::class,
            Listeners\RebindHttpKernelListener::class, // Laravel 7 issue: <https://git.io/JvPpf>
            Listeners\RebindViewListener::class,
            Listeners\RebindAuthorizationGateListener::class,
            Listeners\RebindBroadcastManagerListener::class,
            Listeners\RebindDatabaseManagerListener::class,
            Listeners\RebindMailManagerListener::class,
            Listeners\RebindNotificationChannelManagerListener::class,
            Listeners\RebindPipelineHubListener::class,
            Listeners\RebindQueueManagerListener::class,
            Listeners\RebindValidationFactoryListener::class,
            Listeners\CloneConfigListener::class,
            Listeners\UnqueueCookiesListener::class,
            Listeners\FlushAuthenticationStateListener::class,
            Listeners\ResetSessionListener::class,
            Listeners\ResetProvidersListener::class,
            Listeners\ResetLocaleStateListener::class,

            // Listeners\ResetLaravelScoutListener::class, // for 'laravel/scout' package
            // Listeners\ResetLaravelSocialiteListener::class, // for 'laravel/socialite' package
            // Listeners\ResetInertiaListener::class, // for 'inertiajs/inertia-laravel' package
        ],

        Events\BeforeRequestHandlingEvent::class => [
            Listeners\RebindRouterListener::class,
            Listeners\InjectStatsIntoRequestListener::class,
            Listeners\BindRequestListener::class,
            Listeners\ForceHttpsListener::class,
            Listeners\SetServerPortListener::class,
        ],

        Events\AfterRequestHandlingEvent::class => [
            //
        ],

        Events\AfterLoopIterationEvent::class => [
            Listeners\FlushArrayCacheListener::class,
            Listeners\ResetDatabaseRecordModificationStateListener::class,
            Listeners\ClearInstancesListener::class,
            Listeners\RunGarbageCollectorListener::class,
        ],

        Events\AfterLoopStoppedEvent::class => [
            //
        ],

        Events\LoopErrorOccurredEvent::class => [
            Listeners\SendExceptionToStderrListener::class,
            Listeners\StopWorkerListener::class,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Instances Clearing
    |--------------------------------------------------------------------------
    |
    | Instances described here will be cleared on every request (if
    | `ClearInstancesListener` is enabled).
    |
    */

    'clear_instances' => [
        'auth', // is not required for Laravel >= v8.35
    ],

    /*
    |--------------------------------------------------------------------------
    | Reset Providers
    |--------------------------------------------------------------------------
    |
    | Providers that will be registered on every request (if
    | `ResetProvidersListener` is enabled).
    |
    */

    'reset_providers' => [
        Illuminate\Auth\AuthServiceProvider::class, // is not required for Laravel >= v8.35
        // App\Your\Custom\AuthServiceProvider::class,
        Illuminate\Pagination\PaginationServiceProvider::class, // is not required for Laravel >= v8.35
    ],
];
