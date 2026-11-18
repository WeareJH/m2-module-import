<?php
declare(strict_types=1);

namespace Jh\Import\Source\Sftp;

/**
 * The connection half of an SFTP location - host, credentials, host key pinning. Split out from
 * LocationConfigProviderInterface because it's usually the one thing that doesn't vary per import
 * (one integration partner, several feeds) - resolve and decrypt it once instead of repeating that
 * in every entity's own provider.
 *
 * See LocationConfigProvider for how it's combined with per-import path/pattern config.
 */
interface ConnectionConfigProviderInterface
{
    public function getHost(): string;

    public function getPort(): int;

    public function getUsername(): string;

    /**
     * Null when authenticating via private key instead - see getPrivateKey().
     */
    public function getPassword(): ?string;

    /**
     * Private key contents (PEM), null when authenticating via password instead.
     */
    public function getPrivateKey(): ?string;

    /**
     * Optional known-good host key fingerprint to verify on connect. Null means no verification
     * is performed.
     */
    public function getHostKeyFingerprint(): ?string;
}
