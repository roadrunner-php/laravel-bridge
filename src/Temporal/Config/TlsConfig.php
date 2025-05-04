<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel\Temporal\Config;

/**
 * gRPC TLS configuration.
 */
final readonly class TlsConfig
{
    /**
     * @param non-empty-string|null $rootCerts Root certificates string or file in PEM format.
     *        If null provided, default gRPC root certificates are used.
     * @param non-empty-string|null $privateKey Client private key string or file in PEM format.
     * @param non-empty-string|null $certChain Client certificate chain string or file in PEM format.
     * @param non-empty-string|null $serverName Server name override for TLS verification.
     */
    public function __construct(
        public ?string $rootCerts = null,
        public ?string $privateKey = null,
        public ?string $certChain = null,
        public ?string $serverName = null,
    ) {}
}
