<?php

declare(strict_types = 1);

namespace Spiral\RoadRunnerLaravel\Events;

use Throwable;
use Psr\Http\Message\ServerRequestInterface;
use Illuminate\Contracts\Foundation\Application as ApplicationContract;

final class LoopErrorOccurredEvent implements Contracts\WithApplication, Contracts\WithException, Contracts\WithServerRequest
{
    /**
     * @var ApplicationContract
     */
    private $app;

    /**
     * @var Throwable
     */
    private $exception;

    /**
     * @var ServerRequestInterface
     */
    private $server_request;

    /**
     * Create a new event instance.
     *
     * @param ApplicationContract    $app
     * @param ServerRequestInterface $server_request
     * @param Throwable              $exception
     */
    public function __construct(ApplicationContract $app, ServerRequestInterface $server_request, Throwable $exception)
    {
        $this->app            = $app;
        $this->server_request = $server_request;
        $this->exception      = $exception;
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
    public function exception(): Throwable
    {
        return $this->exception;
    }

    /**
     * {@inheritdoc}
     */
    public function serverRequest(): ServerRequestInterface
    {
        return $this->server_request;
    }
}
