<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel\SocketOptions;

interface SocketOptionsInterface
{
    /**
     * Returns true if an options array contains option by name.
     *
     * @param string $option_name Option name
     *
     * @return bool
     */
    public function hasOption(string $option_name): bool;

    /**
     * Get option by name.
     *
     * @param string $option_name Option name
     *
     * @return mixed
     */
    public function getOption(string $option_name);

    /**
     * Get all options.
     *
     * @return array<mixed>
     */
    public function getOptions(): array;
}
