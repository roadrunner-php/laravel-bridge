<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel\Temporal\Declaration;

final readonly class DeclarationDto
{
    /**
     * @param \ReflectionClass<object> $class
     */
    public function __construct(
        public DeclarationType $type,
        public \ReflectionClass $class,
        public ?string $taskQueue = null,
    ) {}
}
