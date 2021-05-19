<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel\Dumper\Stoppers;

/**
 * @internal
 * @codeCoverageIgnore
 */
final class OsExit implements StopperInterface
{
    private int $exit_code = 1;

    /**
     * @param int $exit_code
     */
    public function setExitCode(int $exit_code): void
    {
        $this->exit_code = $exit_code;
    }

    /**
     * {@inheritDoc}
     */
    public function stop(): void
    {
        exit($this->exit_code);
    }
}
