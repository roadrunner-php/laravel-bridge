<?php

namespace Spiral\RoadRunnerLaravel;

interface WorkerOptionsInterface
{
    /**
     * Get Laravel application base path.
     *
     * @return string
     */
    public function getAppBasePath(): string;

    /**
     * Need to refresh application instance on each request?
     *
     * @return bool
     */
    public function getRefreshApp(): bool;

    /**
     * Get relay data source name.
     *
     * @return string Eg.: `pipes`, `pipes://stdin:stdout`, `tcp://localhost:6001`, `unix:///tmp/rpc.sock`
     */
    public function getRelayDsn(): string;
}
