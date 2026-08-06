<?php
declare(strict_types=1);

namespace Jh\Import\Source\Sftp;

/**
 * Immutable value object describing everything needed to locate, authenticate against, and
 * dispose of files for a single SFTP-sourced feed.
 *
 * Deliberately covers *both* connection detail (host/credentials) and location/pattern/archival
 * detail (remote path/match pattern/archive path) - a filename pattern or a directory is just as
 * much a property of "where this particular feed lives" as the hostname is. Entity import modules
 * never construct this directly; it is built by whatever implements `LocationConfigProviderInterface`
 * for a given import, resolved dynamically per import run (see `Jh\Import\Type\SftpFiles`).
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
     * Remote directory a successfully-imported file should be moved into. Null means: don't
     * archive (only relevant when deleteAfterImport is also false, in which case the file is
     * simply left in place - not recommended for a repeatedly-polled directory).
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
