<?php
declare(strict_types=1);

namespace Jh\Import\Source\Sftp;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Encryption\EncryptorInterface;

use function sprintf;

/**
 * Reads host/port/username/password/private_key/host_key_fingerprint from global scope config
 * under whatever `connectionConfigPathPrefix` is bound, decrypting the password like any other
 * `obscure` admin field.
 *
 * Global scope only - it's meant for the common case of one SFTP account shared across every
 * import (see LocationConfigProvider for how that's combined with per-import path config). If a
 * connection genuinely needs to vary per website, write your own ConnectionConfigProviderInterface
 * implementation rather than bending this one to do both.
 */
class ConnectionConfigProvider implements ConnectionConfigProviderInterface
{
    private const FIELD_HOST = 'host';
    private const FIELD_PORT = 'port';
    private const FIELD_USERNAME = 'username';
    private const FIELD_PASSWORD = 'password';
    private const FIELD_PRIVATE_KEY = 'private_key';
    private const FIELD_HOST_KEY_FINGERPRINT = 'host_key_fingerprint';

    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly EncryptorInterface $encryptor,
        private readonly string $connectionConfigPathPrefix
    ) {
    }

    public function getHost(): string
    {
        return (string) $this->scopeConfig->getValue($this->fieldPath(self::FIELD_HOST));
    }

    public function getPort(): int
    {
        return (int) $this->scopeConfig->getValue($this->fieldPath(self::FIELD_PORT));
    }

    public function getUsername(): string
    {
        return (string) $this->scopeConfig->getValue($this->fieldPath(self::FIELD_USERNAME));
    }

    public function getPassword(): ?string
    {
        $encrypted = $this->scopeConfig->getValue($this->fieldPath(self::FIELD_PASSWORD));

        return $encrypted ? $this->encryptor->decrypt((string) $encrypted) : null;
    }

    public function getPrivateKey(): ?string
    {
        $encrypted = $this->scopeConfig->getValue($this->fieldPath(self::FIELD_PRIVATE_KEY));

        return $encrypted ? $this->encryptor->decrypt((string) $encrypted) : null;
    }

    public function getHostKeyFingerprint(): ?string
    {
        $value = $this->scopeConfig->getValue($this->fieldPath(self::FIELD_HOST_KEY_FINGERPRINT));

        return $value !== null && $value !== '' ? (string) $value : null;
    }

    private function fieldPath(string $field): string
    {
        return sprintf('%s/%s', $this->connectionConfigPathPrefix, $field);
    }
}
