<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel\Tests\Unit\Grpc\Fixture;

use RoadRunner\Jobs\DTO\V1\Stat;
use Spiral\RoadRunner\GRPC\ContextInterface;

final class PlainEchoService implements EchoServiceInterface
{
    public function Echo(ContextInterface $ctx, Stat $in): Stat
    {
        return $in;
    }

    public function Plain(ContextInterface $ctx, Stat $in): Stat
    {
        return $in;
    }
}
