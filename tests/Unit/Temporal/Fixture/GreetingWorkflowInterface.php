<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel\Tests\Unit\Temporal\Fixture;

use Spiral\RoadRunnerLaravel\Temporal\Attribute\AssignWorker;
use Temporal\Workflow\WorkflowInterface;

#[WorkflowInterface]
#[AssignWorker('greetings')]
interface GreetingWorkflowInterface {}
