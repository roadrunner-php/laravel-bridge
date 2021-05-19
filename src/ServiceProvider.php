<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel;

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Events\Dispatcher as EventsDispatcher;

class ServiceProvider extends \Illuminate\Support\ServiceProvider
{
    /**
     * Get config root key name.
     *
     * @return string roadrunner
     */
    public static function getConfigRootKey(): string
    {
        return \basename(static::getConfigPath(), '.php');
    }

    /**
     * Returns path to the configuration file.
     *
     * @return string
     */
    public static function getConfigPath(): string
    {
        return __DIR__ . '/../config/roadrunner.php';
    }

    /**
     * Register package services.
     *
     * @return void
     */
    public function register(): void
    {
        $this->initializeConfigs();

        $this->app->singleton(Dumper\Stack\StackInterface::class, Dumper\Stack\FixedArrayStack::class);
        $this->app->singleton(Dumper\Dumper::class, Dumper\Dumper::class);

        $this->app
            ->when(Dumper\Dumper::class)
            ->needs(Dumper\Stoppers\StopperInterface::class)
            ->give(Dumper\Stoppers\OsExit::class);
    }

    /**
     * Boot package services.
     *
     * @param Kernel           $kernel
     * @param ConfigRepository $config
     * @param EventsDispatcher $events
     *
     * @return void
     */
    public function boot(Kernel $kernel, ConfigRepository $config, EventsDispatcher $events): void
    {
        $this->bootEventListeners($config, $events);
        $this->bootMiddlewares($kernel);
    }

    /**
     * @param ConfigRepository $config
     * @param EventsDispatcher $events
     *
     * @return void
     */
    protected function bootEventListeners(ConfigRepository $config, EventsDispatcher $events): void
    {
        $hashmap = \array_merge_recursive(
            $this->builtInEventListeners(),
            (array) $config->get(static::getConfigRootKey() . '.listeners')
        );

        foreach ($hashmap as $event => $listeners) {
            foreach (\array_filter(\array_unique($listeners)) as $listener) {
                $events->listen($event, $listener);
            }
        }
    }

    /**
     * @return array<string, array<string>>
     */
    protected function builtInEventListeners(): array
    {
        return [
            Events\AfterLoopIterationEvent::class => [
                Listeners\FlushDumperStackListener::class,
            ],
        ];
    }

    /**
     * @param Kernel $kernel
     *
     * @return void
     */
    protected function bootMiddlewares(Kernel $kernel): void
    {
        if ($kernel instanceof \Illuminate\Foundation\Http\Kernel) {
            $kernel->pushMiddleware(Dumper\Middleware::class);
        }
    }

    /**
     * Initialize configs.
     *
     * @return void
     */
    protected function initializeConfigs(): void
    {
        $this->mergeConfigFrom(static::getConfigPath(), static::getConfigRootKey());

        $this->publishes([
            \realpath(static::getConfigPath()) => config_path(\basename(static::getConfigPath())),
        ], 'config');
    }
}
