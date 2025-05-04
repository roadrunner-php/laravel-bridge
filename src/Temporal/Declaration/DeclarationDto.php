<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel\Temporal\Declaration;

final readonly class DeclarationDto
{
    public function __construct(
        public DeclarationType $type,
        public \ReflectionClass $class,
        public ?string $taskQueue = null,
    ) {}
}
