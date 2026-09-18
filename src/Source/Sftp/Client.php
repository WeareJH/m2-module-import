<?php
declare(strict_types=1);

namespace Jh\Import\Source\Sftp;

use phpseclib3\Crypt\PublicKeyLoader;
use phpseclib3\Net\SFTP;
use RuntimeException;

use function in_array;
use function sprintf;

/**
 * Thin wrapper around phpseclib3's SFTP client, built from a LocationConfig. Connects lazily and
 * reuses one connection for its lifetime - callers (Type\SftpFiles) should build one Client per
 * import run and share it across every matched file, not reconnect per file.
 */
class Client
{
    private ?SFTP $connection = null;

    public function __construct(
        private readonly LocationConfig $locationConfig,
        private readonly SftpConnectionFactory $sftpConnectionFactory
    ) {
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
        $remotePath = rtrim($this->locationConfig->getRemotePath(), DIRECTORY_SEPARATOR);
        $listing = $this->connection()->nlist($remotePath);

        if ($listing === false) {
            throw new RuntimeException(sprintf('Could not list remote path "%s"', $remotePath));
        }

        return array_values(array_filter(array_map(
            static fn (string $file): string => $remotePath . DIRECTORY_SEPARATOR . $file,
            $listing
        ), $this->isSafeListingEntry(...)));
    }

    /**
     * Filters out "." and ".." listing entries - not anything whose basename merely contains
     * "..", since a filename like "my-import..csv" is perfectly valid.
     */
    private function isSafeListingEntry(string $file): bool
    {
        $basename = basename($file);

        return $basename !== '.' && $basename !== '..';
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
     * Rejects anything outside the configured remote path, guarding against a malformed or
     * malicious filename. Looks for a literal ".." path segment, not just a ".." substring - a
     * filename can contain "..", only a "/../" segment actually means "go up a directory".
     */
    private function assertWithinRemotePath(string $remoteFile): void
    {
        $remotePath = rtrim($this->locationConfig->getRemotePath(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        $hasTraversalSegment = in_array('..', explode(DIRECTORY_SEPARATOR, $remoteFile), true);

        if ($hasTraversalSegment || !str_starts_with($remoteFile, $remotePath)) {
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

        $sftp = $this->sftpConnectionFactory->create(
            $this->locationConfig->getHost(),
            $this->locationConfig->getPort()
        );

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
     * Verifies the server's host key against a known-good fingerprint, when one is configured, to
     * guard against MITM. No fingerprint means no verification - fine, but not recommended for
     * production feeds.
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
