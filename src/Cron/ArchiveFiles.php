<?php

namespace Jh\Import\Cron;

use Jh\Import\Config;
use Jh\Import\Config\Data;
use Magento\Framework\Filesystem\Directory\WriteFactory;
use Magento\Framework\App\Filesystem\DirectoryList;

/**
 * @author Aydin Hassan <aydin@hotmail.co.uk>
 */
class ArchiveFiles
{
    /**
     * @var Data
     */
    private $config;

    /**
     * @var DirectoryList
     */
    private $directoryList;

    /**
     * @var WriteFactory
     */
    private $writeFactory;

    public function __construct(Data $config, DirectoryList $directoryList, WriteFactory $writeFactory)
    {
        $this->config = $config;
        $this->directoryList = $directoryList;
        $this->writeFactory = $writeFactory;
    }

    public function execute()
    {
        collect($this->config->getAllImportNames())
            ->filter(function (string $importName) {
                return $this->config->getImportType($importName) === 'files';
            })
            ->map(function (string $importName) {
                return $this->config->getImportConfigByName($importName);
            })
            ->filter(function (Config $config) {
                return $config->get('archive_old_files');
            })
            ->each(function (Config $config) {
                $this->archiveOldFilesInDir($config->get('archived_directory'), 'archived');
                $this->archiveOldFilesInDir($config->get('failed_directory'), 'failed');
            });
    }

    private function archiveOldFilesInDir(string $dir, string $type)
    {
        $threeDaysAgo = (new \DateTime)->modify('-3 days');

        $directoryWriter = $this->writeFactory->create(
            sprintf('%s/%s', $this->directoryList->getPath(DirectoryList::VAR_DIR), $dir)
        );
        $directoryWriter->create();

        $files = collect($directoryWriter->read())
            ->map(function (string $filePath) use ($directoryWriter) {
                return new \SplFileInfo($directoryWriter->getAbsolutePath($filePath));
            })
            ->filter(function (\SplFileInfo $file) use ($threeDaysAgo) {
                return new \DateTime('@' . $file->getCTime()) < $threeDaysAgo;
            });

        if ($files->isEmpty()) {
            return;
        }

        $zip = new \ZipArchive();
        $zip->open(
            sprintf(
                '%s/%s-%s.zip',
                $directoryWriter->getAbsolutePath(),
                $type,
                (new \DateTime)->format('d-m-Y-H-i')
            ),
            \ZipArchive::CREATE
        );

        $files->each(function (\SplFileInfo $file) use ($zip) {
            $zip->addFile($file->getRealPath(), $file->getFilename());
        });

        $zip->close();

        $files->each(function (\SplFileInfo $file) use ($directoryWriter) {
            $directoryWriter->delete($file->getRealPath());
        });
    }
}
