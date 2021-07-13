<?php
declare(strict_types=1);

namespace Jh\Import\Type;

use Jh\Import\Config;
use Jh\Import\Import\ImporterFactory;
use Magento\Framework\ObjectManagerInterface;

/**
 * @author Maciej Sławik <maciej@wearejh.com>
 */
class Db implements Type
{
    private ObjectManagerInterface $objectManager;
    private ImporterFactory $importerFactory;

    public function __construct(
        ObjectManagerInterface $objectManager,
        ImporterFactory $importerFactory
    ) {
        $this->objectManager = $objectManager;
        $this->importerFactory = $importerFactory;
    }

    public function run(Config $config)
    {
        $specification = $this->objectManager->get($config->getSpecificationService());
        $writer = $this->objectManager->get($config->getWriterService());
        $source = $this->objectManager->create($config->getSourceService(), [
            'connectionName' => (string) $config->getConnectionName(),
            'idField' => $config->getIdField(),
            'sourceId' => (string) $config->getSourceId(),
            'selectSql' => (string) $config->getSelectSql(),
            'countSql' => (string) $config->getCountSql()
        ]);

        $this->importerFactory
            ->create($source, $specification, $writer)
            ->process($config);
    }
}
