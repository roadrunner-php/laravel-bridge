<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel\Tests\Unit\Temporal;

use Spiral\Attributes\AttributeReader;
use Spiral\RoadRunnerLaravel\Temporal\Config\TemporalConfig;
use Spiral\RoadRunnerLaravel\Temporal\DeclarationWorkerResolver;
use Spiral\RoadRunnerLaravel\Tests\Unit\Temporal\Fixture\GreetingWorkflow;
use Spiral\RoadRunnerLaravel\Tests\Unit\Temporal\Fixture\PaymentActivity;
use Spiral\RoadRunnerLaravel\Tests\Unit\Temporal\Fixture\PlainService;
use Testo\Assert;
use Testo\Test;

#[Test]
final class DeclarationWorkerResolverTest
{
    public function test_every_assigned_worker_is_returned(): void
    {
        $resolver = new DeclarationWorkerResolver(new AttributeReader(), new TemporalConfig());

        Assert::same($resolver->resolve(new \ReflectionClass(PaymentActivity::class)), ['payments', 'billing']);
    }

    public function test_worker_is_inherited_from_an_interface(): void
    {
        $resolver = new DeclarationWorkerResolver(new AttributeReader(), new TemporalConfig());

        Assert::same($resolver->resolve(new \ReflectionClass(GreetingWorkflow::class)), ['greetings']);
    }

    public function test_default_worker_is_used_without_assignment(): void
    {
        $resolver = new DeclarationWorkerResolver(
            new AttributeReader(),
            new TemporalConfig(['defaultWorker' => 'fallback']),
        );

        Assert::same($resolver->resolve(new \ReflectionClass(PlainService::class)), ['fallback']);
    }
}
