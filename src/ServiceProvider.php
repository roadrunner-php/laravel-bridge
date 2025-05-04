<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel;

use Spiral\Attributes\AttributeReader;
use Spiral\Attributes\ReaderInterface;

final class ServiceProvider extends \Illuminate\Support\ServiceProvider
{
    public static function getConfigRootKey(): string
    {
        return \basename(self::getConfigPath(), '.php');
    }

    public static function getConfigPath(): string
    {
        return __DIR__ . '/../config/roadrunner.php';
    }

    public function register(): void
    {
        $this->app->singleton(ReaderInterface::class, AttributeReader::class);
        $this->initializeConfigs();
    }

    protected function initializeConfigs(): void
    {
        $this->mergeConfigFrom(self::getConfigPath(), self::getConfigRootKey());

        $this->publishes([
            \realpath(self::getConfigPath()) => config_path(\basename(self::getConfigPath())),
        ], 'config');
    }
}
