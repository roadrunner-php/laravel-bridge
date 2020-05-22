<?php

namespace Spiral\RoadRunnerLaravel\Events\Contracts;

use Symfony\Component\HttpFoundation\Response;

interface WithHttpResponse
{
    /**
     * Get HTTP response instance.
     *
     * @return Response
     */
    public function httpResponse(): Response;
}
