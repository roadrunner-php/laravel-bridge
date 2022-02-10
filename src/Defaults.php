<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel;

final class Defaults
{
    /**
     * Returns an array of built-in listeners for the {@see Events\BeforeLoopStartedEvent}.
     *
     * @return array<class-string>
     */
    public static function beforeLoopStarted(): array
    {
        return [
            Listeners\FixSymfonyFileValidationListener::class,
            Listeners\FixSymfonyFileMovingListener::class,
            Listeners\WarmInstancesListener::class,

            // 3rd party packages:
            Listeners\SetupTelescopeListener::class, // for <https://github.com/laravel/telescope>
        ];
    }

    /**
     * Returns an array of built-in listeners for the {@see Events\BeforeLoopIterationEvent}.
     *
     * @return array<class-string>
     */
    public static function beforeLoopIteration(): array
    {
        return [
            Listeners\CloneConfigListener::class,
            Listeners\EnableHttpMethodParameterOverrideListener::class,
            Listeners\RebindHttpKernelListener::class, // Laravel 7 issue: <https://git.io/JvPpf>
            Listeners\RebindViewListener::class,
            Listeners\RebindAuthorizationGateListener::class,
            Listeners\RebindBroadcastManagerListener::class,
            Listeners\RebindDatabaseManagerListener::class,
            Listeners\RebindDatabaseSessionHandlerListener::class,
            Listeners\RebindFilesystemManagerListener::class,
            Listeners\RebindMailManagerListener::class,
            Listeners\RebindNotificationChannelManagerListener::class,
            Listeners\RebindPipelineHubListener::class,
            Listeners\RebindQueueManagerListener::class,
            Listeners\RebindValidationFactoryListener::class,
            Listeners\UnqueueCookiesListener::class,
            Listeners\FlushAuthenticationStateListener::class,
            Listeners\ResetSessionListener::class,
            Listeners\ResetProvidersListener::class,
            Listeners\ResetLocaleStateListener::class,

            // 3rd party packages:
            Listeners\ResetLaravelScoutListener::class, // for <https://github.com/laravel/scout>
            Listeners\ResetLaravelSocialiteListener::class, // for <https://github.com/laravel/socialite>
            Listeners\ResetInertiaListener::class, // for <https://github.com/inertiajs/inertia-laravel>
            Listeners\ResetZiggyListener::class, // for <https://github.com/tighten/ziggy>
            Listeners\ResetLivewireListener::class, // for <https://github.com/livewire/livewire>
        ];
    }

    /**
     * Returns an array of built-in listeners for the {@see Events\BeforeRequestHandlingEvent}.
     *
     * @return array<class-string>
     */
    public static function beforeRequestHandling(): array
    {
        return [
            Listeners\RebindRouterListener::class,
            Listeners\BindRequestListener::class,
            Listeners\ForceHttpsListener::class,
            Listeners\SetServerPortListener::class,
        ];
    }

    /**
     * Returns an array of built-in listeners for the {@see Events\AfterRequestHandlingEvent}.
     *
     * @return array<class-string>
     */
    public static function afterRequestHandling(): array
    {
        return [];
    }

    /**
     * Returns an array of built-in listeners for the {@see Events\AfterLoopIterationEvent}.
     *
     * @return array<class-string>
     */
    public static function afterLoopIteration(): array
    {
        return [
            Listeners\FlushDumperStackListener::class,
            Listeners\FlushLogContextListener::class,
            Listeners\FlushArrayCacheListener::class,
            Listeners\FlushStrCacheListener::class,
            Listeners\FlushMonologStateListener::class,
            Listeners\FlushTranslatorCacheListener::class,
            Listeners\ResetDatabaseRecordModificationStateListener::class,
            Listeners\FlushDatabaseQueryLogListener::class,
            Listeners\ClearInstancesListener::class,
        ];
    }

    /**
     * Returns an array of built-in listeners for the {@see Events\AfterLoopStoppedEvent}.
     *
     * @return array<class-string>
     */
    public static function afterLoopStopped(): array
    {
        return [];
    }

    /**
     * Returns an array of built-in listeners for the {@see Events\LoopErrorOccurredEvent}.
     *
     * @return array<class-string>
     */
    public static function loopErrorOccurred(): array
    {
        return [];
    }

    /**
     * Get the container bindings / services that should be pre-resolved before the worker loop starting by default.
     *
     * @return array<string>
     *
     * @see Listeners\WarmInstancesListener
     */
    public static function servicesToWarm(): array
    {
        return [
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
        ];
    }

    /**
     * Get the container bindings / services that should be cleared (flushed) on every worker iteration.
     *
     * @return array<string>
     *
     * @see Listeners\ClearInstancesListener
     */
    public static function servicesToClear(): array
    {
        return [];
    }

    /**
     * Get the service-providers list to reset on every worker loop iteration.
     *
     * @return array<class-string>
     *
     * @see Listeners\ResetProvidersListener
     */
    public static function providersToReset(): array
    {
        return [];
    }
}
