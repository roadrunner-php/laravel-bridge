<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel\Dumper;

use Illuminate\Support\Env;
use Symfony\Component\VarDumper\VarDumper;
use Symfony\Component\VarDumper\Cloner\VarCloner;

class Dumper
{
    /**
     * @var Stack\StackInterface
     */
    protected Stack\StackInterface $stack;

    /**
     * @var Stoppers\StopperInterface
     */
    protected Stoppers\StopperInterface $stopper;

    /**
     * @var VarCloner
     */
    protected VarCloner $cloner;

    /**
     * RR constructor.
     *
     * @param Stack\StackInterface      $stack
     * @param Stoppers\StopperInterface $stopper
     */
    public function __construct(Stack\StackInterface $stack, Stoppers\StopperInterface $stopper)
    {
        $this->stopper = $stopper;
        $this->stack   = $stack;
        $this->cloner  = new VarCloner();
    }

    /**
     * Dump passed values.
     *
     * @param mixed $var
     * @param mixed ...$vars
     *
     * @return void
     *
     * @throws \ErrorException On variables cloner errors
     */
    public function dump($var, ...$vars): void
    {
        $ran_using_cli = $this->ranUsingCLI();

        foreach ([$var, ...$vars] as $item) {
            if ($ran_using_cli) {
                // @codeCoverageIgnoreStart
                VarDumper::dump($item);
                // @codeCoverageIgnoreEnd
            } else {
                $this->stack->push($this->cloner->cloneVar($item));
            }
        }
    }

    /**
     * Dump passed values and stop the execution.
     *
     * @param mixed ...$vars
     *
     * @return void
     *
     * @throws Exceptions\DumperException On execution in non-CLI context
     * @throws \ErrorException On variables cloner errors
     */
    public function dd(...$vars)
    {
        if ($this->ranUsingCLI()) {
            // @codeCoverageIgnoreStart
            try {
                foreach ($vars as $item) {
                    VarDumper::dump($item);
                }
            } finally {
                $this->stopper->stop();
            }
            // @codeCoverageIgnoreEnd
        } else {
            $stack = new Stack\FixedArrayStack();

            foreach ($vars as $item) {
                $stack->push($this->cloner->cloneVar($item));
            }

            throw Exceptions\DumperException::withStack($stack);
        }
    }

    /**
     * @return bool
     */
    protected function ranUsingCLI(): bool
    {
        if (Env::get('APP_RUNNING_IN_CONSOLE') === true) {
            return true;
        }

        /** @link https://roadrunner.dev/docs/php-environment */
        if (Env::get('RR_MODE') !== null && Env::get('RR_RELAY') !== null) {
            return false;
        }

        /** @see VarDumper::register() */
        return \in_array(\PHP_SAPI, ['cli', 'phpdbg'], true);
    }
}
