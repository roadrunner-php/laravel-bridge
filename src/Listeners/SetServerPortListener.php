<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel\Listeners;

use Spiral\RoadRunnerLaravel\Events\Contracts\WithHttpRequest;

/**
 * This listener must be registered AFTER `ForceHttpsListener` for correct links generation.
 *
 * @see ForceHttpsListener
 */
class SetServerPortListener implements ListenerInterface
{
    /**
     * Server port request attribute.
     */
    public const SERVER_PORT_ATTRIBUTE = 'SERVER_PORT';

    /**
     * HTTPS port number.
     */
    public const HTTPS_PORT = 443;

    /**
     * HTTP port number.
     */
    public const HTTP_PORT = 80;

    /**
     * {@inheritdoc}
     */
    public function handle($event): void
    {
        if ($event instanceof WithHttpRequest) {
            $request = $event->httpRequest();

            /** @var int|string|null $port */
            $port = $request->getPort();

            if ($port === null || $port === '') {
                if ($request->getScheme() === 'https') {
                    $request->server->set(static::SERVER_PORT_ATTRIBUTE, static::HTTPS_PORT);
                } elseif ($request->getScheme() === 'http') {
                    $request->server->set(static::SERVER_PORT_ATTRIBUTE, static::HTTP_PORT);
                }
            }
        }
    }
}
