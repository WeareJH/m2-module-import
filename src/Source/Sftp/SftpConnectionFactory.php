<?php
declare(strict_types=1);

namespace Jh\Import\Source\Sftp;

use phpseclib3\Net\SFTP;

/**
 * Hand-written, not Magento-generated: SFTP is a phpseclib3 class, not ours, and relying on
 * Magento's DI generator for vendor classes is asking for trouble - it used to autogenerate
 * factories for arbitrary library classes on older versions, then stopped.
 */
class SftpConnectionFactory
{
    public function create(string $host, int $port): SFTP
    {
        return new SFTP($host, $port);
    }
}
