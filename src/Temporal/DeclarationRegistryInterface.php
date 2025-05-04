<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel\Temporal;

use Spiral\RoadRunnerLaravel\Temporal\Declaration\DeclarationDto;

interface DeclarationRegistryInterface
{
    /**
     * Add a new declaration to the registry.
     *
     * @param class-string $class Workflow or activity class name.
     */
    public function addDeclaration(string $class): void;

    /**
     * List all declarations.
     *
     * @return iterable<DeclarationDto>
     */
    public function getDeclarationList(): iterable;
}
