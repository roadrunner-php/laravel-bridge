<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel;

interface WorkerOptionsInterface
{
    /**
     * Get Laravel application base path.
     */
    public function getAppBasePath(): string;

    /**
     * Get relay data source name.
     *
     * @return non-empty-string Eg.: `pipes`, `pipes://stdin:stdout`, `tcp://localhost:6001`, `unix:///tmp/rpc.sock`
     */
    public function getRelayDsn(): string;
}
