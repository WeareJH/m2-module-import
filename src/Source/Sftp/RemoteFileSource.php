<?php
declare(strict_types=1);

namespace Jh\Import\Source\Sftp;

/**
 * A Source backed by one specific remote file over SFTP. Implemented by every SFTP-backed Source
 * (SftpCsv, SftpXml, ...) regardless of the remote file's actual format, so SftpArchiver can
 * archive/delete the file afterwards without caring which format it was parsed as.
 */
interface RemoteFileSource
{
    public function getClient(): Client;

    public function getRemoteFile(): string;
}
