<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel\Tests\Unit\Temporal;

use Spiral\Attributes\AttributeReader;
use Spiral\RoadRunnerLaravel\Temporal\Declaration\DeclarationType;
use Spiral\RoadRunnerLaravel\Temporal\DeclarationRegistry;
use Spiral\RoadRunnerLaravel\Tests\Unit\Temporal\Fixture\AbstractWorkflow;
use Spiral\RoadRunnerLaravel\Tests\Unit\Temporal\Fixture\GreetingWorkflow;
use Spiral\RoadRunnerLaravel\Tests\Unit\Temporal\Fixture\GreetingWorkflowInterface;
use Spiral\RoadRunnerLaravel\Tests\Unit\Temporal\Fixture\PaymentActivity;
use Spiral\RoadRunnerLaravel\Tests\Unit\Temporal\Fixture\PlainService;
use Testo\Assert;
use Testo\Test;

#[Test]
final class DeclarationRegistryTest
{
    public function test_workflow_is_detected_through_its_interface(): void
    {
        $registry = new DeclarationRegistry(new AttributeReader());
        $registry->addDeclaration(GreetingWorkflow::class);

        $list = [...$registry->getDeclarationList()];

        Assert::count($list, 1);
        Assert::same($list[0]->type, DeclarationType::Workflow);
        Assert::same($list[0]->class->getName(), GreetingWorkflow::class);
        Assert::null($list[0]->taskQueue);
    }

    public function test_activity_is_detected_on_the_class(): void
    {
        $registry = new DeclarationRegistry(new AttributeReader());
        $registry->addDeclaration(PaymentActivity::class);

        $list = [...$registry->getDeclarationList()];

        Assert::count($list, 1);
        Assert::same($list[0]->type, DeclarationType::Activity);
    }

    public function test_interfaces_abstract_and_plain_classes_are_ignored(): void
    {
        $registry = new DeclarationRegistry(new AttributeReader());
        $registry->addDeclaration(GreetingWorkflowInterface::class);
        $registry->addDeclaration(AbstractWorkflow::class);
        $registry->addDeclaration(PlainService::class);

        Assert::same([...$registry->getDeclarationList()], []);
    }
}
