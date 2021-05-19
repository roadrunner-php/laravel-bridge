<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel\Facades;

/**
 * @method static void dump($var, ...$vars) Dump passed values
 * @method static void dd(...$vars)
 *
 * @see \Spiral\RoadRunner\RR
 */
final class RR extends \Illuminate\Support\Facades\Facade
{
    /**
     * {@inheritDoc}
     */
    protected static function getFacadeAccessor(): string
    {
        return \Spiral\RoadRunnerLaravel\RR::class;
    }
}
