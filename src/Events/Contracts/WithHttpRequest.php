<?php

namespace Spiral\RoadRunnerLaravel\Events\Contracts;

use Symfony\Component\HttpFoundation\Request;

interface WithHttpRequest
{
    /**
     * Get HTTP request instance.
     *
     * @return Request
     */
    public function httpRequest(): Request;
}
