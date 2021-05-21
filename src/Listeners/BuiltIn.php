<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel\Listeners;

use Spiral\RoadRunnerLaravel\Events;

final class BuiltIn
{
    /**
     * Returns an array of built-in listeners for the BeforeLoopStartedEvent.
     *
     * @return array<class-string>
     *
     * @see Events\BeforeLoopStartedEvent
     */
    public static function beforeLoopStarted(): array
    {
        return [
            FixSymfonyFileValidationListener::class,
            WarmInstancesListener::class,
        ];
    }

    /**
     * Returns an array of built-in listeners for the BeforeLoopIterationEvent.
     *
     * @return array<class-string>
     *
     * @see Events\BeforeLoopIterationEvent
     */
    public static function beforeLoopIteration(): array
    {
        return [
            CloneConfigListener::class,
            EnableHttpMethodParameterOverrideListener::class,
            RebindHttpKernelListener::class, // Laravel 7 issue: <https://git.io/JvPpf>
            RebindViewListener::class,
            RebindAuthorizationGateListener::class,
            RebindBroadcastManagerListener::class,
            RebindDatabaseManagerListener::class,
            RebindMailManagerListener::class,
            RebindNotificationChannelManagerListener::class,
            RebindPipelineHubListener::class,
            RebindQueueManagerListener::class,
            RebindValidationFactoryListener::class,
            UnqueueCookiesListener::class,
            FlushAuthenticationStateListener::class,
            ResetSessionListener::class,
            ResetProvidersListener::class,
            ResetLocaleStateListener::class,
        ];
    }

    /**
     * Returns an array of built-in listeners for the BeforeRequestHandlingEvent.
     *
     * @return array<class-string>
     *
     * @see Events\BeforeRequestHandlingEvent
     */
    public static function beforeRequestHandling(): array
    {
        return [
            RebindRouterListener::class,
            InjectStatsIntoRequestListener::class,
            BindRequestListener::class,
            ForceHttpsListener::class,
            SetServerPortListener::class,
        ];
    }

    /**
     * Returns an array of built-in listeners for the AfterRequestHandlingEvent.
     *
     * @return array<class-string>
     *
     * @see Events\AfterRequestHandlingEvent
     */
    public static function afterRequestHandling(): array
    {
        return [];
    }

    /**
     * Returns an array of built-in listeners for the AfterLoopIterationEvent.
     *
     * @return array<class-string>
     *
     * @see Events\AfterLoopIterationEvent
     */
    public static function afterLoopIteration(): array
    {
        return [
            FlushDumperStackListener::class,
            FlushArrayCacheListener::class,
            ResetDatabaseRecordModificationStateListener::class,
            ClearInstancesListener::class,
        ];
    }

    /**
     * Returns an array of built-in listeners for the AfterLoopStoppedEvent.
     *
     * @return array<class-string>
     *
     * @see Events\AfterLoopStoppedEvent
     */
    public static function afterLoopStopped(): array
    {
        return [];
    }

    /**
     * Returns an array of built-in listeners for the LoopErrorOccurredEvent.
     *
     * @return array<class-string>
     *
     * @see Events\LoopErrorOccurredEvent
     */
    public static function loopErrorOccurred(): array
    {
        return [];
    }
}
