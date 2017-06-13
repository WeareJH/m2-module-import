<?php

namespace Jh\Import\Cron;

use Jh\Import\Config;
use Jh\Import\Config\Data;
use Magento\Framework\Filesystem\Directory\WriteFactory;
use Magento\Framework\App\Filesystem\DirectoryList;

/**
 * @author Aydin Hassan <aydin@hotmail.co.uk>
 */
class DeleteFiles
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
                return $config->get('clean_up_dirs');
            })
            ->each(function (Config $config) {
                $this->deleteOldFilesInDir($config->get('archived_directory'));
                $this->deleteOldFilesInDir($config->get('failed_directory'));
            });
    }

    private function deleteOldFilesInDir(string $dir)
    {
        $twoWeeksAgo = (new \DateTime)->modify('-14 days');

        $directoryWriter = $this->writeFactory->create(
            sprintf('%s/%s', $this->directoryList->getPath(DirectoryList::VAR_DIR), $dir)
        );
        $directoryWriter->create();

        collect($directoryWriter->read())
            ->map(function (string $filePath) use ($directoryWriter) {
                return new \SplFileInfo($directoryWriter->getAbsolutePath($filePath));
            })
            ->filter(function (\SplFileInfo $file) use ($twoWeeksAgo) {
                return new \DateTime('@' . $file->getCTime()) < $twoWeeksAgo;
            })
            ->each(function (\SplFileInfo $file) use ($directoryWriter) {
                $directoryWriter->delete($file->getFilename());
            });
    }
}
