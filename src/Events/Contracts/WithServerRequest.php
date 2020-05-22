<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel\Events\Contracts;

use Psr\Http\Message\ServerRequestInterface;

interface WithServerRequest
{
    /**
     * Get server request instance.
     *
     * @return ServerRequestInterface
     */
    public function serverRequest(): ServerRequestInterface;
}
