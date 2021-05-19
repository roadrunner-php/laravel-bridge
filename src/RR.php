<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel;

class RR
{
    /**
     * @var Dumper\Dumper
     */
    private Dumper\Dumper $dumper;

    /**
     * RR constructor.
     */
    public function __construct(Dumper\Dumper $dumper)
    {
        $this->dumper = $dumper;
    }

    /**
     * Dump passed values.
     *
     * @param mixed $var
     * @param mixed ...$vars
     *
     * @return void
     */
    public function dump($var, ...$vars): void
    {
        $this->dumper->dump($var, ...$vars);
    }

    /**
     * Dump passed values and stop the execution (die).
     *
     * @param mixed ...$vars
     *
     * @return void
     */
    public function dd(...$vars)
    {
        $this->dumper->dd(...$vars);
    }
}
