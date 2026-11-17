<?php
declare(strict_types=1);

namespace Jh\Import\Type;

use Exception;
use Jh\Import\Config;
use Jh\Import\Import\Importer;
use Jh\Import\Import\ImporterFactory;
use Jh\Import\Source\Sftp\ClientFactory;
use Jh\Import\Source\Sftp\LocationConfigProviderFactory;
use Jh\Import\Source\SourceFactory;
use Jh\Import\Specification\SpecificationFactory;
use Jh\Import\Writer\WriterFactory;
use Psr\Log\LoggerInterface;

use function basename;
use function sprintf;
use function strnatcmp;

class SftpFiles implements Type
{
    public function __construct(
        private readonly ImporterFactory $importerFactory,
        private readonly ClientFactory $clientFactory,
        private readonly SourceFactory $sourceFactory,
        private readonly FileMatcher $fileMatcher,
        private readonly LocationConfigProviderFactory $locationConfigProviderFactory,
        private readonly SpecificationFactory $specificationFactory,
        private readonly WriterFactory $writerFactory,
        private readonly LoggerInterface $logger
    ) {
    }

    public function run(Config $config)
    {
        $locationConfigProvider = $this->locationConfigProviderFactory->create($config->getRequired('location_config_provider'));
        $locationConfig = $locationConfigProvider->getLocationConfig();

        // One connection per import run, shared across every matched file - not one per file.
        $client = $this->clientFactory->create($locationConfig);

        // The server lists files in no guaranteed order. Delta feeds need the last file applied to be the newest one
        // (and process_only_last_file needs "last" to mean newest), so files are processed in name order: feeds name
        // their files with a sequence number or a timestamp.
        $filesToProcess = $this->fileMatcher
            ->matched($locationConfig->getMatchPattern(), $client->listFiles())
            ->sort(static fn (string $first, string $second): int => strnatcmp(basename($first), basename($second)))
            ->values();

        $specification = $this->specificationFactory->create($config->getSpecificationService());
        $writer = $this->writerFactory->create($config->getWriterService());

        $lastFileIndex = $filesToProcess->count() - 1;
        $filesToProcess->each(function ($file, $index) use ($config, $specification, $writer, $lastFileIndex, $client) {
            $source = $this->sourceFactory->create($config, [
                'client' => $client,
                'remoteFile' => $file,
            ]);

            $importer = $this->importerFactory->create($source, $specification, $writer);

            if ($config->get('process_only_last_file') && $index < $lastFileIndex) {
                $this->skip($importer, $config, $file);
            } else {
                $importer->process($config);
            }
        });
    }

    /**
     * A skipped file is not imported, so failing to move it aside must not stop the files after it. It is logged, not
     * reported: a report needs the source id, which downloads the file, and that fails the same way if the connection
     * is what broke.
     */
    private function skip(Importer $importer, Config $config, string $file): void
    {
        try {
            $importer->skip($config);
        } catch (Exception $exception) {
            $this->logger->error(
                sprintf('Could not move skipped SFTP file "%s" aside: %s', $file, $exception->getMessage())
            );
        }
    }
}
