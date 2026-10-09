<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel\Tests\Unit\Temporal\Fixture;

use Spiral\RoadRunnerLaravel\Temporal\Attribute\AssignWorker;
use Temporal\Activity\ActivityInterface;

#[ActivityInterface]
#[AssignWorker('payments')]
#[AssignWorker('billing')]
final class PaymentActivity {}
