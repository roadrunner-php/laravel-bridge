<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel\Tests\Unit\Temporal\Fixture;

use Illuminate\Container\Container;
use Temporal\Activity\ActivityInterface;
use Temporal\Activity\ActivityMethod;

#[ActivityInterface(prefix: 'probe.')]
final class SandboxProbe
{
    public function __construct(private readonly \DomainException $failure) {}

    #[ActivityMethod]
    public function sandbox(): int
    {
        return \spl_object_id(Container::getInstance());
    }

    #[ActivityMethod]
    public function fail(): never
    {
        throw $this->failure;
    }
}
