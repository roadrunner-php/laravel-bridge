<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel;

class WorkerOptions implements WorkerOptionsInterface
{
    /**
     * @var string
     */
    protected $base_path;

    /**
     * @var bool
     */
    protected $refresh_app;

    /**
     * @var string
     */
    protected $relay_dsn;

    /**
     * WorkerOptions constructor.
     *
     * @param string $base_path
     * @param bool   $refresh_app
     * @param string $relay_dsn
     */
    public function __construct(string $base_path, bool $refresh_app = false, string $relay_dsn = 'pipes')
    {
        $this->base_path = $base_path;
        $this->refresh_app = $refresh_app;
        $this->relay_dsn = $relay_dsn;
    }

    /**
     * {@inheritDoc}
     */
    public function getAppBasePath(): string
    {
        return $this->base_path;
    }

    /**
     * {@inheritDoc}
     */
    public function getRefreshApp(): bool
    {
        return $this->refresh_app;
    }

    /**
     * {@inheritDoc}
     */
    public function getRelayDsn(): string
    {
        return $this->relay_dsn;
    }
}
