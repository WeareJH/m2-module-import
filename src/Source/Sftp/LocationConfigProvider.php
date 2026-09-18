<?php
declare(strict_types=1);

namespace Jh\Import\Source\Sftp;

use Magento\Framework\App\Config\ScopeConfigInterface;

use function sprintf;

/**
 * Combines a shared ConnectionConfigProviderInterface (host/credentials, resolved once) with
 * per-import path/pattern config read from whatever `locationConfigPathPrefix` is bound. Supports
 * "one SFTP connection, many imports" out of the box.
 *
 * A new sftpfiles entity sharing the existing connection needs no new PHP - just another virtual
 * type in di.xml pointing this class at a different `locationConfigPathPrefix`, plus the matching
 * system.xml fields.
 *
 * Reads {prefix}/remote_path, {prefix}/match_pattern, {prefix}/archive_path and
 * {prefix}/delete_after_import.
 *
 * If one entity's connection really does need to differ (a different SFTP account, not just a
 * different directory), give it its own ConnectionConfigProviderInterface instead - this class
 * doesn't care whether the one it's handed is shared or not.
 */
class LocationConfigProvider implements LocationConfigProviderInterface
{
    private const FIELD_REMOTE_PATH = 'remote_path';
    private const FIELD_MATCH_PATTERN = 'match_pattern';
    private const FIELD_ARCHIVE_PATH = 'archive_path';
    private const FIELD_DELETE_AFTER_IMPORT = 'delete_after_import';

    public function __construct(
        private readonly ConnectionConfigProviderInterface $connectionConfigProvider,
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly string $locationConfigPathPrefix
    ) {
    }

    public function getLocationConfig(): LocationConfig
    {
        return new LocationConfig(
            host: $this->connectionConfigProvider->getHost(),
            port: $this->connectionConfigProvider->getPort(),
            username: $this->connectionConfigProvider->getUsername(),
            password: $this->connectionConfigProvider->getPassword(),
            privateKey: $this->connectionConfigProvider->getPrivateKey(),
            remotePath: (string) $this->scopeConfig->getValue($this->fieldPath(self::FIELD_REMOTE_PATH)),
            matchPattern: (string) $this->scopeConfig->getValue($this->fieldPath(self::FIELD_MATCH_PATTERN)),
            archivePath: $this->getArchivePath(),
            deleteAfterImport: $this->scopeConfig->isSetFlag($this->fieldPath(self::FIELD_DELETE_AFTER_IMPORT)),
            hostKeyFingerprint: $this->connectionConfigProvider->getHostKeyFingerprint()
        );
    }

    private function getArchivePath(): ?string
    {
        $value = $this->scopeConfig->getValue($this->fieldPath(self::FIELD_ARCHIVE_PATH));

        return $value !== null && $value !== '' ? (string) $value : null;
    }

    private function fieldPath(string $field): string
    {
        return sprintf('%s/%s', $this->locationConfigPathPrefix, $field);
    }
}
