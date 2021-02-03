<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel;

class RunParams
{
    /**
     * @var bool
     */
    protected $app_refresh;

    /**
     * @var string|null
     */
    protected $socket_type;

    /**
     * @var string|null
     */
    protected $socket_address;

    /**
     * @var int|null
     */
    protected $socket_port;

    /**
     * @return bool
     */
    public function isAppRefresh(): bool
    {
        return $this->app_refresh;
    }

    /**
     * @param bool $app_refresh
     *
     * @return RunParams
     */
    public function setAppRefresh(bool $app_refresh): RunParams
    {
        $this->app_refresh = $app_refresh;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getSocketType(): ?string
    {
        return $this->socket_type;
    }

    /**
     * @param mixed $socket_type
     *
     * @return RunParams
     */
    public function setSocketType($socket_type): RunParams
    {
        $this->socket_type = \is_string($socket_type)
            ? $socket_type
            : null;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getSocketAddress(): ?string
    {
        return $this->socket_address;
    }

    /**
     * @param mixed $socket_address
     *
     * @return RunParams
     */
    public function setSocketAddress($socket_address): RunParams
    {
        $this->socket_address = \is_string($socket_address)
            ? $socket_address
            : null;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getSocketPort(): ?int
    {
        return $this->socket_port;
    }

    /**
     * @param mixed $socket_port
     *
     * @return RunParams
     */
    public function setSocketPort($socket_port): RunParams
    {
        $this->socket_port = \is_string($socket_port)
            ? (int) $socket_port
            : null;

        return $this;
    }
}
