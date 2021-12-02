<?php

declare(strict_types=1);

namespace rr;

use Illuminate\Container\Container;
use Spiral\RoadRunner\Http\PSR7Worker;
use Spiral\RoadRunnerLaravel\Dumper\Dumper;
use Illuminate\Contracts\Container\BindingResolutionException;

if (!\function_exists('\\rr\\dump')) {
    /**
     * Dump passed values.
     *
     * USE THIS FUNCTION ONLY FOR DEBUGGING.
     *
     * @param mixed $var
     * @param mixed ...$vars
     *
     * @return void
     *
     * @throws \Throwable
     */
    function dump($var, ...$vars): void
    {
        /** @var Dumper $dumper */
        $dumper = Container::getInstance()->make(Dumper::class);

        $dumper->dump($var, ...$vars);
    }
}

if (!\function_exists('\\rr\\dd')) {
    /**
     * Dump passed values and stop the execution (exit).
     *
     * USE THIS FUNCTION ONLY FOR DEBUGGING.
     *
     * @param mixed ...$vars
     *
     * @return void
     *
     * @throws \Throwable
     */
    function dd(...$vars): void
    {
        /** @var Dumper $dumper */
        $dumper = Container::getInstance()->make(Dumper::class);

        $dumper->dd(...$vars);
    }
}

if (!\function_exists('\\rr\\worker')) {
    /**
     * Get the RoadRunner PSR worker.
     *
     * USE THIS FUNCTION ONLY FOR DEBUGGING.
     *
     * @return PSR7Worker
     *
     * @throws BindingResolutionException If called outside of RR worker context
     */
    function worker(): PSR7Worker
    {
        $worker = Container::getInstance()->make(PSR7Worker::class);

        if ($worker instanceof PSR7Worker) {
            return $worker;
        }

        throw new BindingResolutionException;
    }
}
