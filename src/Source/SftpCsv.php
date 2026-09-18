<?php
declare(strict_types=1);

namespace Jh\Import\Source;

use Countable;
use Jh\Import\Report\Report;
use Jh\Import\Source\Sftp\Client;
use Magento\Framework\Filesystem\Driver\File;
use RuntimeException;

/**
 * Downloads the remote file to a local temp file and hands off all the actual row-parsing to Csv
 * (composition, not duplication). A future feed needing a different remote file format should be
 * a new sibling Source class reusing the same Sftp\Client/LocationConfig transport, not a reason
 * to grow this one.
 */
class SftpCsv implements Source, Countable
{
    private ?Csv $csv = null;
    private ?string $localTempFile = null;

    public function __construct(
        private readonly Client $client,
        private readonly string $remoteFile,
        private readonly CsvFactory $csvFactory,
        private readonly File $fileDriver,
        private readonly string $delimiter = ',',
        private readonly string $enclosure = '"',
        private readonly string $escape = '\\',
        private readonly int $headerRowNum = 0
    ) {
    }

    public function traverse(callable $onSuccess, callable $onError, Report $report): void
    {
        $this->csv()->traverse($onSuccess, $onError, $report);
    }

    /**
     * Delegates to Csv, which hashes the downloaded file's content, so the same content is
     * always detected as a duplicate/already-imported regardless of its remote filename.
     */
    public function getSourceId(): string
    {
        return $this->csv()->getSourceId();
    }

    public function count(): int
    {
        return $this->csv()->count();
    }

    /**
     * Downloaded and wrapped on first use rather than eagerly in the constructor, so just
     * instantiating this class never costs a download we might not end up needing.
     */
    private function csv(): Csv
    {
        if ($this->csv !== null) {
            return $this->csv;
        }

        $localTempFile = tempnam(sys_get_temp_dir(), 'jh_import_sftp_');

        if ($localTempFile === false) {
            throw new RuntimeException('Could not create a local temp file to download the remote SFTP file into');
        }

        $this->localTempFile = $localTempFile;
        $this->client->get($this->remoteFile, $this->localTempFile);

        return $this->csv = $this->csvFactory->create([
            'file' => $this->localTempFile,
            'delimiter' => $this->delimiter,
            'enclosure' => $this->enclosure,
            'escape' => $this->escape,
            'headerRowNum' => $this->headerRowNum,
        ]);
    }

    public function __destruct()
    {
        if ($this->localTempFile !== null && $this->fileDriver->isExists($this->localTempFile)) {
            $this->fileDriver->deleteFile($this->localTempFile);
        }
    }
}
