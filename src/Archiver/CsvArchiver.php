<?php

namespace Jh\Import\Archiver;

use DateTime;
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

    /**
     * @var string
     */
    private static $importDirectory = 'jh_import';

    public function __construct(Csv $source, DirectoryList $directoryList, File $filesystem, DateTime $date = null)
    {
        $this->source = $source;
        $this->filesystem = $filesystem;
        $this->directoryList = $directoryList;
        $this->date = $date;
    }

    public function failed()
    {
        $this->ensureDirectoryExists(sprintf('%s/failed', $this->rootPath()));
        $this->filesystem->rename(
            $this->source->getFile()->getRealPath(),
            sprintf('%s/failed/%s', $this->rootPath(), $this->newName($this->source->getFile()))
        );
    }

    public function successful()
    {
        $this->ensureDirectoryExists(sprintf('%s/archived', $this->rootPath()));
        $this->filesystem->rename(
            $this->source->getFile()->getRealPath(),
            sprintf('%s/archived/%s', $this->rootPath(), $this->newName($this->source->getFile()))
        );
    }

    private function ensureDirectoryExists(string $directory)
    {
        if (!$this->filesystem->isExists($directory)) {
            $this->filesystem->createDirectory($directory, 0777);
        }
    }

    private function rootPath() : string
    {
        return sprintf('%s/%s', $this->directoryList->getPath(DirectoryList::VAR_DIR), static::$importDirectory);
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
