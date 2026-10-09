<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel;

class WorkerOptions implements WorkerOptionsInterface
{
    /**
     * @param non-empty-string $relayDsn
     */
    public function __construct(
        protected string $basePath,
        protected string $relayDsn = 'pipes',
    ) {}

    #[\Override]
    public function getAppBasePath(): string
    {
        return $this->basePath;
    }

    #[\Override]
    public function getRelayDsn(): string
    {
        return $this->relayDsn;
    }
}
