<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel\Temporal\Config;

/**
 * Temporal connection and credentials configuration.
 *
 * How to connect to local Temporal server:
 *
 *      new ConnectionConfig('localhost:7233'),
 *
 * How to connect to Temporal Cloud:
 *
 *      (new ConnectionConfig('foo-bar-default.baz.tmprl.cloud:7233'))
 *          ->withTls(
 *              privateKey: '/my-project.key',
 *              certChain: '/my-project.pem',
 *          ),
 */
final readonly class ConnectionConfig
{
    /**
     * @param non-empty-string $address Address of the Temporal service.
     * @param TlsConfig|null $tls TLS configuration for the connection.
     * @param non-empty-string|\Stringable|null $authToken Authentication token for the service client.
     */
    public function __construct(
        public string $address,
        public ?TlsConfig $tls = null,
        public string|\Stringable|null $authToken = null,
    ) {}

    /**
     * Check if the connection is secure.
     *
     * @psalm-assert-if-true TlsConfig $this->tls
     * @psalm-assert-if-false null $this->tls
     */
    public function isSecure(): bool
    {
        return $this->tls !== null;
    }

    /**
     * Set the TLS configuration for the connection.
     *
     * @param non-empty-string|null $rootCerts Root certificates string or file in PEM format.
     *         If null provided, default gRPC root certificates are used.
     * @param non-empty-string|null $privateKey Client private key string or file in PEM format.
     * @param non-empty-string|null $certChain Client certificate chain string or file in PEM format.
     * @param non-empty-string|null $serverName Server name override for TLS verification.
     */
    public function withTls(
        ?string $rootCerts = null,
        ?string $privateKey = null,
        ?string $certChain = null,
        ?string $serverName = null,
    ): self {
        return new self(
            $this->address,
            new TlsConfig($rootCerts, $privateKey, $certChain, $serverName),
            $this->authToken,
        );
    }

    /**
     * Set the authentication token for the service client.
     *
     * This is the equivalent of providing an "Authorization" header with "Bearer " + the given key.
     * This will overwrite any "Authorization" header that may be on the context before each request to the
     * Temporal service.
     * You may pass your own {@see \Stringable} implementation to be able to change the key dynamically.
     *
     * @param non-empty-string|\Stringable|null $authToken
     */
    public function withAuthKey(string|\Stringable|null $authToken): self
    {
        return new self(
            $this->address,
            $this->tls,
            $authToken,
        );
    }
}
