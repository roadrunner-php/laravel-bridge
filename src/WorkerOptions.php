<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel;

class WorkerOptions implements WorkerOptionsInterface
{
    public function __construct(
        protected string $basePath,
        protected string $relayDsn = 'pipes',
    ) {}

    public function getAppBasePath(): string
    {
        return $this->basePath;
    }

    public function getRelayDsn(): string
    {
        return $this->relayDsn;
    }
}
