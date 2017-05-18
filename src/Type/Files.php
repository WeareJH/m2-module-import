<?php

namespace Jh\Import\Type;

use Jh\Import\Config;
use Jh\Import\Import\ImporterFactory;
use Illuminate\Support\Collection;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem\Directory\WriteFactory;
use Magento\Framework\ObjectManagerInterface;

/**
 * @author Aydin Hassan <aydin@wearejh.com>
 */
class Files implements Type
{
    /**
     * @var DirectoryList
     */
    private $directoryList;

    /**
     * @var WriteFactory
     */
    private $writeFactory;

    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var ImporterFactory
     */
    private $importerFactory;

    public function __construct(
        DirectoryList $directoryList,
        WriteFactory $writeFactory,
        ObjectManagerInterface $objectManager,
        ImporterFactory $importerFactory
    ) {
        $this->directoryList = $directoryList;
        $this->writeFactory = $writeFactory;
        $this->objectManager = $objectManager;
        $this->importerFactory = $importerFactory;
    }

    public function run(Config $config)
    {
        $filesToProcess = $this->getFilesToProcess($config);

        $specification = $this->objectManager->get($config->getSpecificationService());
        $writer        = $this->objectManager->get($config->getWriterService());

        $filesToProcess->each(function ($file) use ($config, $specification, $writer) {
            $source = $this->objectManager->create($config->getSourceService(), [
                'file' => $file
            ]);

            $this->importerFactory
                ->create($source, $specification, $writer)
                ->process($config);
        });
    }

    private function getFilesToProcess(Config $config) : Collection
    {
        $directoryWriter = $this->writeFactory->create(
            sprintf(
                '%s/%s',
                $this->directoryList->getPath(DirectoryList::VAR_DIR),
                $config->get('incoming_directory')
            )
        );

        //ensure directory is created
        $directoryWriter->create();

        $files = collect($directoryWriter->read())
            ->map(function (string $file) use ($directoryWriter) {
                return $directoryWriter->getAbsolutePath($file);
            });

        if ($config->get('match_files') === '*') {
            return $files;
        }

        //treat as regex
        if ($config->get('match_files')[0] === '/') {
            return $files->filter(function ($file) use ($config) {
                return preg_match($config->get('match_files'), pathinfo($file, PATHINFO_BASENAME));
            });
        }

        //else treat as single file match
        return $files->filter(function ($file) use ($config) {
            return $config->get('match_files') === pathinfo($file, PATHINFO_BASENAME);
        });
    }
}
