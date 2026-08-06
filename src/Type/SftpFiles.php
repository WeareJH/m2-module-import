<?php
declare(strict_types=1);

namespace Jh\Import\Type;

use Jh\Import\Config;
use Jh\Import\Import\ImporterFactory;
use Jh\Import\Source\Sftp\ClientFactory;
use Jh\Import\Source\Sftp\LocationConfigProviderInterface;
use Magento\Framework\ObjectManagerInterface;

class SftpFiles implements Type
{
    public function __construct(
        private readonly ObjectManagerInterface $objectManager,
        private readonly ImporterFactory $importerFactory,
        private readonly ClientFactory $clientFactory,
        private readonly FileMatcher $fileMatcher
    ) {
    }

    public function run(Config $config)
    {
        /** @var LocationConfigProviderInterface $locationConfigProvider */
        $locationConfigProvider = $this->objectManager->get($config->getRequired('location_config_provider'));
        $locationConfig = $locationConfigProvider->getLocationConfig();

        // One connection per import run, shared across every matched file - not one per file.
        $client = $this->clientFactory->create($locationConfig);

        $filesToProcess = $this->fileMatcher->matched($locationConfig->getMatchPattern(), $client->listFiles());

        $specification = $this->objectManager->get($config->getSpecificationService());
        $writer        = $this->objectManager->get($config->getWriterService());

        $lastFileIndex = $filesToProcess->count() - 1;
        $filesToProcess->each(function ($file, $index) use ($config, $specification, $writer, $lastFileIndex, $client) {
            $source = $this->objectManager->create($config->getSourceService(), [
                'client' => $client,
                'remoteFile' => $file,
            ]);

            $importer = $this->importerFactory->create($source, $specification, $writer);

            if ($config->get('process_only_last_file') && $index < $lastFileIndex) {
                $importer->skip($config);
            } else {
                $importer->process($config);
            }
        });
    }
}
