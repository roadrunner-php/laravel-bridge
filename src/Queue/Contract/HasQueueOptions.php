<?php

namespace Spiral\RoadRunnerLaravel\Queue\Contract;

interface HasQueueOptions
{
    public function queueOptions(): array;
}
