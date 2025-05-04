<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel\Temporal;

use Spiral\Attributes\ReaderInterface;
use Spiral\RoadRunnerLaravel\Temporal\Declaration\DeclarationDto;
use Spiral\RoadRunnerLaravel\Temporal\Declaration\DeclarationType;
use Temporal\Activity\ActivityInterface;
use Temporal\Workflow\WorkflowInterface;

final class DeclarationRegistry implements DeclarationRegistryInterface
{
    /**
     * @var DeclarationDto[]
     */
    private array $declarations = [];

    public function __construct(
        private readonly ReaderInterface $reader,
    ) {}

    public function addDeclaration(string $class): void
    {
        $this->prepareDeclaration(new \ReflectionClass($class));
    }

    public function getDeclarationList(): iterable
    {
        return $this->declarations;
    }

    private function prepareDeclaration(\ReflectionClass $class): void
    {
        if ($class->isAbstract() || $class->isInterface() || $class->isEnum()) {
            return;
        }

        /** @var DeclarationType|null $type */
        $type = null;

        foreach (\array_merge($class->getInterfaces(), [$class]) as $reflection) {
            if ($this->reader->firstClassMetadata($reflection, WorkflowInterface::class) !== null) {
                $type = DeclarationType::Workflow;
                break;
            }

            if ($this->reader->firstClassMetadata($reflection, ActivityInterface::class) !== null) {
                $type = DeclarationType::Activity;
                break;
            }
        }

        if ($type !== null) {
            $this->declarations[] = new DeclarationDto(
                type: $type,
                class: $class,
                taskQueue: null,
            );
        }
    }
}
