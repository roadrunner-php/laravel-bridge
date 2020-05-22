<?php

declare(strict_types = 1);

namespace Spiral\RoadRunnerLaravel\Tests\Listeners;

/**
 * @deprecated Rewrite using mock object
 */
class FakeServiceProvider extends \Illuminate\Support\ServiceProvider
{
    /**
     * @var callable|null
     */
    public static $onConstructor;

    /**
     * @var callable|null
     */
    public static $onRegister;

    /**
     * @var callable|null
     */
    public static $onBoot;

    /**
     * Create a new faked service provider instance.
     *
     * @param mixed ...$args
     */
    public function __construct(...$args)
    {
        if (\is_callable(static::$onConstructor)) {
            (static::$onConstructor)(...$args);
        }

        parent::__construct(...$args);
    }

    /**
     * @param mixed ...$args
     *
     * @return void
     */
    public function register(...$args): void
    {
        if (\is_callable(static::$onRegister)) {
            (static::$onRegister)(...$args);
        }
    }

    /**
     * @param mixed ...$args
     *
     * @return void
     */
    public function boot(...$args): void
    {
        if (\is_callable(static::$onBoot)) {
            (static::$onBoot)(...$args);
        }
    }
}
