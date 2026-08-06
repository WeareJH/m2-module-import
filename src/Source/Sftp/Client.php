<?php
declare(strict_types=1);

namespace Jh\Import\Source\Sftp;

use phpseclib3\Crypt\PublicKeyLoader;
use phpseclib3\Net\SFTP;
use RuntimeException;

use function sprintf;

/**
 * Thin wrapper around phpseclib3's SFTP client, built from a LocationConfig. Connects lazily on
 * first use and reuses a single connection for the lifetime of this instance - callers
 * (Type\SftpFiles) should build one Client per import run and share it across every matched file,
 * rather than reconnecting per file.
 */
class Client
{
    private ?SFTP $connection = null;

    public function __construct(private readonly LocationConfig $locationConfig)
    {
    }

    public function getLocationConfig(): LocationConfig
    {
        return $this->locationConfig;
    }

    /**
     * Contains 1st level files absolute paths
     * @return string[]
     */
    public function listFiles(): array
    {
        $remotePath = rtrim($this->locationConfig->getRemotePath(), '/');
        $listing = $this->connection()->nlist($remotePath);

        if ($listing === false) {
            throw new RuntimeException(sprintf('Could not list remote path "%s"', $remotePath));
        }

        return array_values(array_filter(array_map(
            static fn (string $file): string => $remotePath . '/' . $file,
            $listing
        ), $this->isSafeListingEntry(...)));
    }

    /**
     * Guard against path traversal via an unexpectedly-shaped listing entry - every file we act
     * on must resolve strictly within the configured remote path.
     */
    private function isSafeListingEntry(string $file): bool
    {
        $basename = basename($file);

        return $basename !== '.' && $basename !== '..' && !str_contains($basename, '..');
    }

    public function get(string $remoteFile, string $localFile): void
    {
        $this->assertWithinRemotePath($remoteFile);

        if ($this->connection()->get($remoteFile, $localFile) === false) {
            throw new RuntimeException(sprintf('Could not download remote file "%s"', $remoteFile));
        }
    }

    public function rename(string $remoteFile, string $newRemoteFile): void
    {
        $this->assertWithinRemotePath($remoteFile);

        if (!$this->connection()->rename($remoteFile, $newRemoteFile)) {
            throw new RuntimeException(
                sprintf('Could not rename remote file "%s" to "%s"', $remoteFile, $newRemoteFile)
            );
        }
    }

    public function delete(string $remoteFile): void
    {
        $this->assertWithinRemotePath($remoteFile);

        if (!$this->connection()->delete($remoteFile)) {
            throw new RuntimeException(sprintf('Could not delete remote file "%s"', $remoteFile));
        }
    }

    /**
     * Reject anything that isn't strictly within the configured remote path - defends against a
     * malformed/malicious filename causing an operation outside the intended directory.
     */
    private function assertWithinRemotePath(string $remoteFile): void
    {
        $remotePath = rtrim($this->locationConfig->getRemotePath(), '/') . '/';

        if (str_contains($remoteFile, '..') || !str_starts_with($remoteFile, $remotePath)) {
            throw new RuntimeException(
                sprintf(
                    'Refusing to operate on "%s" - outside the configured remote path "%s"',
                    $remoteFile,
                    $remotePath
                )
            );
        }
    }

    private function connection(): SFTP
    {
        if ($this->connection !== null) {
            return $this->connection;
        }

        $sftp = new SFTP($this->locationConfig->getHost(), $this->locationConfig->getPort());

        $this->verifyHostKey($sftp);

        $privateKey = $this->locationConfig->getPrivateKey();
        $authenticated = $privateKey !== null
            ? $sftp->login($this->locationConfig->getUsername(), PublicKeyLoader::load($privateKey))
            : $sftp->login($this->locationConfig->getUsername(), (string) $this->locationConfig->getPassword());

        if (!$authenticated) {
            throw new RuntimeException(
                sprintf('Could not authenticate with SFTP host "%s"', $this->locationConfig->getHost())
            );
        }

        return $this->connection = $sftp;
    }

    /**
     * Pin/verify the server's host key against a known-good fingerprint, when one is configured.
     * Guards against MITM on the SFTP link. No fingerprint configured => no verification (allowed,
     * but not recommended for production feeds - see the architecture doc's Security section).
     */
    private function verifyHostKey(SFTP $sftp): void
    {
        $expectedFingerprint = $this->locationConfig->getHostKeyFingerprint();

        if ($expectedFingerprint === null) {
            return;
        }

        $actualFingerprint = $sftp->getServerPublicHostKey();

        if ($actualFingerprint === false || !hash_equals($expectedFingerprint, $actualFingerprint)) {
            throw new RuntimeException(
                sprintf('Host key mismatch for SFTP host "%s" - refusing to connect', $this->locationConfig->getHost())
            );
        }
    }
}
