<?php

namespace Spiral\RoadRunnerLaravel\Dumper\Stack;

/**
 * Last-In First-Out stack.
 *
 * @internal
 */
interface StackInterface extends \Countable
{
    /**
     * Get the last element from the stack, shortening the stack by one element. If stack does not contains any
     * elements - default value will be returned.
     *
     * @param mixed|null $default
     *
     * @return mixed|null
     */
    public function pop($default = null);

    /**
     * Push one or more elements onto the end of stack.
     *
     * @param mixed ...$items
     */
    public function push(...$items): void;

    /**
     * Iterate all stack elements from the end to start. Iterated elements will be removed from the stack.
     *
     * @return \Iterator<mixed>
     */
    public function all(): \Iterator;

    /**
     * Clear the stack.
     *
     * @return void
     */
    public function flush(): void;
}
