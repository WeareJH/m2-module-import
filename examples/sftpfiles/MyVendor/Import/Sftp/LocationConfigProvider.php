<?php

declare(strict_types=1);

namespace MyVendor\Import\Sftp;

use Jh\Import\Source\Sftp\LocationConfig;
use Jh\Import\Source\Sftp\LocationConfigProviderInterface;

/**
 * Minimal example of an SFTP location config provider - resolves the remote host, credentials
 * and paths a `sftpfiles` import needs to connect and know what to read.
 *
 * Constructor arguments here are bound via di.xml with literal placeholder values purely so this
 * example is a complete, wireable module. A real implementation would typically read these from
 * Magento's own scoped config (`ScopeConfigInterface`) with credentials stored via an `obscure`
 * field type + `Magento\Config\Model\Config\Backend\Encrypted`, rather than from di.xml arguments
 * - see Jh\Import\Source\Sftp\LocationConfigProviderInterface for why this is a separate
 * interface at all: it lets the connection detail vary per install/environment without the
 * Specification/Writer classes ever needing to know or care.
 */
class LocationConfigProvider implements LocationConfigProviderInterface
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

    public function getLocationConfig(): LocationConfig
    {
        return new LocationConfig(
            $this->host,
            $this->port,
            $this->username,
            $this->password,
            $this->privateKey,
            $this->remotePath,
            $this->matchPattern,
            $this->archivePath,
            $this->deleteAfterImport,
            $this->hostKeyFingerprint
        );
    }
}
