<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel\Tests\Unit\Queue\Fixture;

use Spiral\RoadRunner\Jobs\Options;
use Spiral\RoadRunner\Jobs\OptionsInterface;
use Spiral\RoadRunnerLaravel\Queue\Contract\HasQueueOptions;

final class PrioritizedJob implements HasQueueOptions
{
    public function queueOptions(): OptionsInterface
    {
        return new Options(delay: 3, priority: 42);
    }
}
