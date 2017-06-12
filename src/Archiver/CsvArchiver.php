<?php

namespace Jh\Import\Archiver;

use DateTime;
use Jh\Import\Config;
use Jh\Import\Source\Csv;
use Magento\Framework\App\Filesystem\DirectoryList;
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
     * @var DateTime
     */
    private $date;

    public function __construct(
        Csv $source,
        Config $config,
        DirectoryList $directoryList,
        File $filesystem,
        DateTime $date = null
    ) {
        $this->source = $source;
        $this->config = $config;
        $this->filesystem = $filesystem;
        $this->directoryList = $directoryList;
        $this->date = $date;
    }

    public function failed()
    {
        $this->ensureDirectoryExists(
            sprintf(
                '%s/%s',
                $this->directoryList->getPath(DirectoryList::VAR_DIR),
                $this->config->get('failed_directory')
            )
        );

        $this->filesystem->rename(
            $this->source->getFile()->getRealPath(),
            sprintf(
                '%s/%s/%s',
                $this->directoryList->getPath(DirectoryList::VAR_DIR),
                $this->config->get('failed_directory'),
                $this->newName($this->source->getFile())
            )
        );
    }

    public function successful()
    {
        $this->ensureDirectoryExists(
            sprintf(
                '%s/%s',
                $this->directoryList->getPath(DirectoryList::VAR_DIR),
                $this->config->get('archived_directory')
            )
        );

        $this->filesystem->rename(
            $this->source->getFile()->getRealPath(),
            sprintf(
                '%s/%s/%s',
                $this->directoryList->getPath(DirectoryList::VAR_DIR),
                $this->config->get('archived_directory'),
                $this->newName($this->source->getFile())
            )
        );
    }

    private function ensureDirectoryExists(string $directory)
    {
        if (!$this->filesystem->isExists($directory)) {
            $this->filesystem->createDirectory($directory, 0777);
        }
    }

    private function newName(\SplFileObject $file) : string
    {
        return sprintf(
            '%s-%s.%s',
            $file->getBasename('.' . $file->getExtension()),
            $this->getDateTime()->format('dmYhis'),
            $file->getExtension()
        );
    }

    private function getDateTime() : DateTime
    {
        return $this->date ?? new DateTime;
    }
}
