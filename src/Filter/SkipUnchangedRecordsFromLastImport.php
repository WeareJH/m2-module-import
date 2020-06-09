<?php

declare(strict_types=1);

namespace Jh\Import\Filter;

use Jh\Import\Archiver\Csv\Entity\ArchiveResource;
use Jh\Import\Config;
use Jh\Import\Entity\ImportHistory;
use Jh\Import\Entity\ImportHistoryResource;
use Jh\Import\Import\Record;
use Jh\Import\Entity\ImportHistoryFactory;
use Jh\Import\Import\RequiresPreparation;
use Jh\Import\Source\SourceConsumer;
use Jh\Import\Source\SourceFactory;
use Magento\Framework\DataObject;
use Magento\Framework\App\Filesystem\DirectoryList;

class SkipUnchangedRecordsFromLastImport implements RequiresPreparation
{
    /**
     * @var Config
     */
    private $importConfig;

    /**
     * @var ImportHistoryResource
     */
    private $importHistoryResource;

    /**
     * @var ArchiveResource
     */
    private $csvArchiveResource;

    /**
     * @var DirectoryList
     */
    private $directoryList;

    /**
     * @var SourceFactory
     */
    private $sourceFactory;

    /**
     * @var SourceConsumer
     */
    private $sourceConsumer;

    /**
     * @var ImportHistory
     */
    private $previousImport;

    /**
     * @var Record[]
     */
    private $previousImportData = [];

    public function __construct(
        ImportHistoryResource $importHistoryResource,
        ArchiveResource $csvArchiveResource,
        DirectoryList $directoryList,
        SourceFactory $sourceFactory,
        SourceConsumer $sourceConsumer
    ) {
        $this->importHistoryResource = $importHistoryResource;
        $this->csvArchiveResource = $csvArchiveResource;
        $this->directoryList = $directoryList;
        $this->sourceFactory = $sourceFactory;
        $this->sourceConsumer = $sourceConsumer;
    }

    public function prepare(Config $config): void
    {
        $this->importConfig = $config;

        $this->previousImport = $this->importHistoryResource->getLastImportByName(
            $this->importConfig->getImportName()
        );

        if ($this->previousImport->getId() === null) {
            //first import of this type
            return;
        }

        $archive = $this->csvArchiveResource
            ->getBySourceId($this->previousImport->getData('source_id'));

        if (!$archive->isFileAvailable()) {
            //file has been deleted or archived (zipped)
            return;
        }

        $this->loadPreviousImport(
            sprintf(
                '%s/%s',
                $this->directoryList->getPath(DirectoryList::VAR_DIR),
                $archive['file_location']
            )
        );
    }

    private function loadPreviousImport(string $filePath) : void
    {
        //TODO: Bail out of file does not exist
        //TODO: from manual deletion
        $source = $this->sourceFactory
            ->create(
                $this->importConfig,
                ['file' => $filePath]
            );

        //TODO: Do we even need the data in memory?
        //TODO: We could probably read the file as an iterator (thus not loading at once)
        //TODO: And then only keep the hash in memory
        $prevData = $this->sourceConsumer
            ->toArray($source, $this->importConfig);

        $this->previousImportData = collect($prevData)
            ->keyBy(function (Record $record) {
                return $this->hashRecord($record);
            })
            ->toArray();
    }

    public function __invoke(Record $record)
    {
        if (!$this->previousImport) {
            //if we have no previous import then
            //no records should be filtered
            return true;
        }

        return !isset($this->previousImportData[$this->hashRecord($record)]);
    }

    private function hashRecord(Record $record): string
    {
        return md5(json_encode($record->asArray(), JSON_THROW_ON_ERROR));
    }
}
