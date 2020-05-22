<?php

declare(strict_types = 1);

namespace Spiral\RoadRunnerLaravel\Events;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Spiral\RoadRunnerLaravel\Events\Contracts\WithApplication;
use Spiral\RoadRunnerLaravel\Events\Contracts\WithHttpRequest;
use Spiral\RoadRunnerLaravel\Events\Contracts\WithHttpResponse;
use Illuminate\Contracts\Foundation\Application as ApplicationContract;

final class AfterRequestHandlingEvent implements WithApplication, WithHttpRequest, WithHttpResponse
{
    /**
     * @var ApplicationContract
     */
    private $app;

    /**
     * @var Request
     */
    private $request;

    /**
     * @var Response
     */
    private $response;

    /**
     * Create a new event instance.
     *
     * @param ApplicationContract $app
     * @param Request             $request
     * @param Response            $response
     */
    public function __construct(ApplicationContract $app, Request $request, Response $response)
    {
        $this->app      = $app;
        $this->request  = $request;
        $this->response = $response;
    }

    /**
     * {@inheritdoc}
     */
    public function application(): ApplicationContract
    {
        return $this->app;
    }

    /**
     * {@inheritdoc}
     */
    public function httpRequest(): Request
    {
        return $this->request;
    }

    /**
     * {@inheritdoc}
     */
    public function httpResponse(): Response
    {
        return $this->response;
    }
}
