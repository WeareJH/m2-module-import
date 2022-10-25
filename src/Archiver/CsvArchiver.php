<?php

namespace Jh\Import\Archiver;

use DateTime;
use Jh\Import\Config;
use Jh\Import\Source\Csv;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Filesystem\Driver\File;

/**
 * @author Aydin Hassan <aydin@wearejh.com>
 */
class CsvArchiver implements Archiver
{
    /**
     * @var Csv
     */
    private $source;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var File
     */
    private $filesystem;

    /**
     * @var DirectoryList
     */
    private $directoryList;

    /**
     * @var \Magento\Framework\DB\Adapter\Pdo\Mysql
     */
    private $adapter;

    /**
     * @var DateTime
     */
    private $date;

    public function __construct(
        Csv $source,
        Config $config,
        DirectoryList $directoryList,
        File $filesystem,
        ResourceConnection $resourceConnection,
        DateTime $date = null
    ) {
        $this->source = $source;
        $this->config = $config;
        $this->filesystem = $filesystem;
        $this->directoryList = $directoryList;
        $this->date = $date;
        $this->adapter = $resourceConnection->getConnection();
    }

    public function failed(): void
    {
        $this->ensureDirectoryExists(
            sprintf(
                '%s/%s',
                $this->directoryList->getPath(DirectoryList::VAR_DIR),
                $this->config->get('failed_directory')
            )
        );

        $destination = sprintf(
            "%s/%s",
            $this->config->get('failed_directory'),
            $this->newName($this->source->getFile())
        );

        $this->filesystem->rename(
            $this->source->getFile()->getRealPath(),
            sprintf(
                '%s/%s',
                $this->directoryList->getPath(DirectoryList::VAR_DIR),
                $destination
            )
        );

        $this->adapter->insert(
            'jh_import_archive_csv',
            [
                'source_id' => $this->source->getSourceId() ,
                'file_location' => $destination,
            ]
        );
    }

    public function successful(): void
    {
        $this->ensureDirectoryExists(
            sprintf(
                '%s/%s',
                $this->directoryList->getPath(DirectoryList::VAR_DIR),
                $this->config->get('archived_directory')
            )
        );

        $destination = sprintf(
            "%s/%s",
            $this->config->get('archived_directory'),
            $this->newName($this->source->getFile())
        );

        $this->filesystem->rename(
            $this->source->getFile()->getRealPath(),
            sprintf(
                '%s/%s',
                $this->directoryList->getPath(DirectoryList::VAR_DIR),
                $destination
            )
        );

        $this->adapter->insert(
            'jh_import_archive_csv',
            [
                'source_id' => $this->source->getSourceId() ,
                'file_location' => $destination,
            ]
        );
    }

    private function ensureDirectoryExists(string $directory)
    {
        if (!$this->filesystem->isExists($directory)) {
            $this->filesystem->createDirectory($directory, $this->config->get('directory_permissions'));
        }
    }

    protected function newName(\SplFileObject $file): string
    {
        return sprintf(
            '%s-%s.%s',
            $file->getBasename('.' . $file->getExtension()),
            $this->getDateTime()->format($this->config->get('archive_date_format')),
            $file->getExtension()
        );
    }

    private function getDateTime(): DateTime
    {
        return $this->date ?? new DateTime();
    }
}
