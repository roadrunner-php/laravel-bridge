<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel\Temporal\Attribute;

#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::IS_REPEATABLE)]
final readonly class AssignWorker
{
    /**
     * @param string $taskQueue Task queue name.
     */
    public function __construct(
        public string $taskQueue,
    ) {}
}
