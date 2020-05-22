<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel;

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
    }

    /**
     * Boot package services.
     *
     * @param ConfigRepository $config
     * @param EventsDispatcher $events
     *
     * @return void
     */
    public function boot(ConfigRepository $config, EventsDispatcher $events): void
    {
        $this->bootEventListeners($config, $events);
    }

    /**
     * @param ConfigRepository $config
     * @param EventsDispatcher $events
     *
     * @return void
     */
    protected function bootEventListeners(ConfigRepository $config, EventsDispatcher $events): void
    {
        foreach ((array) $config->get(static::getConfigRootKey() . '.listeners') as $event => $listeners) {
            foreach (\array_filter(\array_unique($listeners)) as $listener) {
                $events->listen($event, $listener);
            }
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

        if (\is_string($rr_config = $this->getRoadRunnerSimpleConfigPath())) {
            $this->publishes([
                $rr_config => $this->app->basePath() . DIRECTORY_SEPARATOR . '.rr.yaml.dist',
            ], 'rr-config');
        }
    }

    /**
     * Get path to the RoadRunner simple config file (if it possible).
     *
     * @return string|null
     */
    protected function getRoadRunnerSimpleConfigPath(): ?string
    {
        $vendor = \dirname((string) (new \ReflectionClass(\Composer\Autoload\ClassLoader::class))->getFileName(), 2);
        $path   = (string) \realpath($vendor . '/spiral/roadrunner/.rr.yaml');

        return \is_file($path)
            ? $path
            : null;
    }
}
