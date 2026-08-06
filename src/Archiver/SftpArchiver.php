<?php
declare(strict_types=1);

namespace Jh\Import\Archiver;

use DateTime;
use JhImport\Import\Archiver\Archiver;
use JhImport\Import\Config;
use JhImport\Import\Source\SftpCsv;

use function sprintf;

class SftpArchiver implements Archiver
{
    public function __construct(
        private readonly SftpCsv $source,
        private readonly Config $config,
        private readonly ?DateTime $date = null
    ) {
    }

    public function successful(): void
    {
        $client = $this->source->getClient();
        $locationConfig = $client->getLocationConfig();
        $remoteFile = $this->source->getRemoteFile();

        if ($locationConfig->isDeleteAfterImport()) {
            $client->delete($remoteFile);
            return;
        }

        $archivePath = $locationConfig->getArchivePath();

        if ($archivePath === null) {
            return;
        }

        $client->rename($remoteFile, rtrim($archivePath, '/') . '/' . $this->newName($remoteFile));
    }

    public function failed(): void
    {
        //File left as it is for easier debugging
    }

    private function newName(string $remoteFile): string
    {
        $basename = basename($remoteFile);
        $extension = pathinfo($basename, PATHINFO_EXTENSION);
        $filenameWithoutExtension = pathinfo($basename, PATHINFO_FILENAME);
        $format = $this->config->get('archive_date_format') ?? 'dmYhis';

        return sprintf(
            '%s-%s%s',
            $filenameWithoutExtension,
            ($this->date ?? new DateTime())->format($format),
            $extension !== '' ? ".{$extension}" : ''
        );
    }
}