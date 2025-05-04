<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel\Temporal\Declaration;

enum DeclarationType: string
{
    case Workflow = 'workflow';
    case Activity = 'activity';
}
