<?php
declare(strict_types=1);

namespace Jh\Import\Source;

use Countable;
use Jh\Import\Report\Report;
use Jh\Import\Source\Sftp\Client;
use Jh\Import\Source\Sftp\RemoteFileSource;
use Magento\Framework\Filesystem\Driver\File;
use RuntimeException;

/**
 * XML equivalent of SftpCsv - downloads the remote file to a local temp file and hands off all
 * the actual row-parsing to Xml. See SftpCsv's own docblock for the composition reasoning; this
 * class exists so an SFTP feed can be pointed at XML instead of CSV purely via imports.xml's
 * `<source>` (or a di.xml override for `recordElement`), no new PHP required.
 */
class SftpXml implements Source, Countable, RemoteFileSource
{
    private ?Xml $xml = null;
    private ?string $localTempFile = null;

    public function __construct(
        private readonly Client $client,
        private readonly string $remoteFile,
        private readonly XmlFactory $xmlFactory,
        private readonly File $fileDriver,
        private readonly string $recordElement = 'item'
    ) {
    }

    public function traverse(callable $onSuccess, callable $onError, Report $report): void
    {
        $this->xml()->traverse($onSuccess, $onError, $report);
    }

    public function getSourceId(): string
    {
        return $this->xml()->getSourceId();
    }

    public function count(): int
    {
        return $this->xml()->count();
    }

    public function getClient(): Client
    {
        return $this->client;
    }

    public function getRemoteFile(): string
    {
        return $this->remoteFile;
    }

    /**
     * Downloaded and wrapped on first use rather than eagerly in the constructor - see SftpCsv's
     * own csv() for why.
     */
    private function xml(): Xml
    {
        if ($this->xml !== null) {
            return $this->xml;
        }

        $localTempFile = tempnam(sys_get_temp_dir(), 'jh_import_sftp_');

        if ($localTempFile === false) {
            throw new RuntimeException('Could not create a local temp file to download the remote SFTP file into');
        }

        $this->localTempFile = $localTempFile;
        $this->client->get($this->remoteFile, $this->localTempFile);

        return $this->xml = $this->xmlFactory->create([
            'file' => $this->localTempFile,
            'recordElement' => $this->recordElement,
        ]);
    }

    public function __destruct()
    {
        if ($this->localTempFile !== null && $this->fileDriver->isExists($this->localTempFile)) {
            $this->fileDriver->deleteFile($this->localTempFile);
        }
    }
}
