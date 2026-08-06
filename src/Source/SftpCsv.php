<?php
declare(strict_types=1);

namespace Jh\Import\Source;

use Countable;
use Jh\Import\Report\Report;
use Jh\Import\Source\Sftp\Client;
use RuntimeException;

/**
 * Downloads the remote file to a local temp file and delegates all actual row-parsing/validation
 * to Csv (composition, not duplication). If a future feed needs a different remote file format
 * over SFTP, that's a new sibling Source class reusing the same Sftp\Client/LocationConfig
 * transport helpers - not something this class needs to grow to cover.
 */
class SftpCsv implements Source, Countable
{
    private readonly Csv $csv;
    private readonly string $localTempFile;

    public function __construct(
        private readonly Client $client,
        private readonly string $remoteFile,
        string $delimiter = ',',
        string $enclosure = '"',
        string $escape = '\\',
        int $headerRowNum = 0
    ) {
        $localTempFile = tempnam(sys_get_temp_dir(), 'jh_import_sftp_');

        if ($localTempFile === false) {
            throw new RuntimeException('Could not create a local temp file to download the remote SFTP file into');
        }

        $this->localTempFile = $localTempFile;
        $this->client->get($this->remoteFile, $this->localTempFile);
        $this->csv = new Csv($this->localTempFile, $delimiter, $enclosure, $escape, $headerRowNum);
    }

    public function traverse(callable $onSuccess, callable $onError, Report $report): void
    {
        $this->csv->traverse($onSuccess, $onError, $report);
    }

    /**
     * Delegates to Csv, which hashes the downloaded file's content - so the same remote file
     * content is always detected as a duplicate/already-imported, regardless of its remote
     * filename
     */
    public function getSourceId(): string
    {
        return $this->csv->getSourceId();
    }

    public function count(): int
    {
        return $this->csv->count();
    }

    public function __destruct()
    {
        if (is_file($this->localTempFile)) {
            @unlink($this->localTempFile);
        }
    }
}
