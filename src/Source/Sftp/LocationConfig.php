<?php
declare(strict_types=1);

namespace Jh\Import\Source\Sftp;

/**
 * Immutable value object describing everything needed to locate, authenticate against, and
 * dispose of files for a single SFTP-sourced feed.
 *
 * Covers both connection detail (host/credentials) and location/pattern/archival detail (remote
 * path/match pattern/archive path) - a match pattern is just as much part of "where this feed
 * lives" as the hostname is. Entity import modules never construct this directly; whatever
 * implements `LocationConfigProviderInterface` for a given import builds it, resolved dynamically
 * per run (see `Jh\Import\Type\SftpFiles`).
 */
class LocationConfig
{
    public function __construct(
        private readonly string $host,
        private readonly int $port,
        private readonly string $username,
        private readonly ?string $password,
        private readonly ?string $privateKey,
        private readonly string $remotePath,
        private readonly string $matchPattern,
        private readonly ?string $archivePath = null,
        private readonly bool $deleteAfterImport = false,
        private readonly ?string $hostKeyFingerprint = null
    ) {
    }

    public function getHost(): string
    {
        return $this->host;
    }

    public function getPort(): int
    {
        return $this->port;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    /**
     * Null when authenticating via private key instead - see getPrivateKey().
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    /**
     * Private key contents (PEM), null when authenticating via password instead.
     */
    public function getPrivateKey(): ?string
    {
        return $this->privateKey;
    }

    /**
     * Remote directory to list for matching files, e.g. "/pricing_import/".
     */
    public function getRemotePath(): string
    {
        return $this->remotePath;
    }

    /**
     * Filename match pattern - same semantics as Jh\Import\Type\FileMatcher::matched(): "*" for
     * everything, a leading "/" for a regex, otherwise an exact filename.
     */
    public function getMatchPattern(): string
    {
        return $this->matchPattern;
    }

    /**
     * Remote directory a successfully-imported file gets moved into. Null and deleteAfterImport
     * false means the file is just left in place - not recommended for a repeatedly-polled
     * directory.
     */
    public function getArchivePath(): ?string
    {
        return $this->archivePath;
    }

    /**
     * When true, a successfully-imported file is deleted rather than archived - takes precedence
     * over getArchivePath().
     */
    public function isDeleteAfterImport(): bool
    {
        return $this->deleteAfterImport;
    }

    /**
     * Optional known-good host key fingerprint to verify on connect, guarding against MITM.
     * Null means no verification is performed (not recommended for production locations).
     */
    public function getHostKeyFingerprint(): ?string
    {
        return $this->hostKeyFingerprint;
    }
}
