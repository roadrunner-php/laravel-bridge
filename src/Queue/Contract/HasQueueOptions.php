<?php

namespace Spiral\RoadRunnerLaravel\Queue\Contract;

use Spiral\RoadRunner\Jobs\OptionsInterface;

interface HasQueueOptions
{
    public function queueOptions(): OptionsInterface;
}
