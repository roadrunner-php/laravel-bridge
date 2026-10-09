<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel\Tests\Unit\Grpc\Fixture;

use RoadRunner\Jobs\DTO\V1\Stat;
use Spiral\RoadRunner\GRPC\ContextInterface;
use Spiral\RoadRunner\GRPC\ServiceInterface;

interface EchoServiceInterface extends ServiceInterface
{
    public function Echo(ContextInterface $ctx, Stat $in): Stat;

    public function Plain(ContextInterface $ctx, Stat $in): Stat;
}
