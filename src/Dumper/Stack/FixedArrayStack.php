<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel\Dumper\Stack;

use Symfony\Component\VarDumper\Cloner\Data;

/**
 * Last-In First-Out stack implementation.
 *
 * @internal
 *
 * @link https://en.wikipedia.org/wiki/Stack_(abstract_data_type)
 */
final class FixedArrayStack implements StackInterface
{
    /**
     * @var \SplFixedArray<mixed>
     */
    private \SplFixedArray $stack;

    /**
     * Stack constructor.
     */
    public function __construct()
    {
        $this->stack = new \SplFixedArray(0);
    }

    /**
     * {@inheritDoc}
     */
    public function count(): int
    {
        return $this->stack->count();
    }

    /**
     * {@inheritDoc}
     */
    public function pop($default = null)
    {
        if (($size = $this->stack->getSize()) > 0) {
            $item = $this->stack->offsetGet($size - 1);

            $this->stack->setSize($size - 1);

            return $item;
        }

        return $default;
    }

    /**
     * {@inheritDoc}
     */
    public function push(...$items): void
    {
        $this->stack->setSize(($pos = $this->stack->getSize()) + ($count = count($items)));

        for ($i = 0; $i < $count; $i++) {
            $this->stack->offsetSet($pos + $i, $items[$i]);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function all(): \Iterator
    {
        while ($this->count() > 0) {
            yield $this->pop();
        }
    }

    /**
     * {@inheritDoc}
     */
    public function flush(): void
    {
        $this->stack->setSize(0);
    }
}
