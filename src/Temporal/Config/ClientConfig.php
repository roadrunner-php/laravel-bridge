<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel\Temporal\Config;

use Temporal\Client\ClientOptions;
use Temporal\Client\GRPC\Context;
use Temporal\Client\GRPC\ContextInterface;

/**
 * Temporal Client configuration.
 *
 *     new ClientConfig(
 *         connection: new ConnectionConfig(
 *             address: 'localhost:7233',
 *             tls: new TlsConfig(
 *                 privateKey: '/my-project.key',
 *                 certChain: '/my-project.pem',
 *             ),
 *         ),
 *         options: (new ClientOptions())
 *             ->withNamespace('default'),
 *         context: Context::default()
 *             ->withTimeout(4.5)
 *             ->withRetryOptions(
 *                 RpcRetryOptions::new()
 *                     ->withMaximumAttempts(5)
 *                     ->withInitialInterval(3)
 *                     ->withMaximumInterval(10)
 *                     ->withBackoffCoefficient(1.6)
 *             ),
 *     ),
 */
final readonly class ClientConfig
{
    public ContextInterface $context;

    /**
     * Create a new client configuration.
     *
     * @param ClientOptions $options Workflow or Schedule client options.
     * @param ContextInterface|null $context Default Service Client context.
     */
    public function __construct(
        public ConnectionConfig $connection,
        public ClientOptions $options = new ClientOptions(),
        ?ContextInterface $context = null,
    ) {
        $this->context = $context ?? Context::default();
    }
}
