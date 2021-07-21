<?php
declare(strict_types=1);

namespace Jh\Import\Import;

use Jh\Import\Config\Data;
use Jh\Import\Type\Db;
use Jh\Import\Type\Files;
use Jh\Import\Type\Type;
use Magento\Framework\ObjectManagerInterface;

/**
 * @author Aydin Hassan <aydin@wearejh.com>
 */
class Manager
{
    /**
     * @var Data
     */
    private Data $config;

    /**
     * @var ObjectManagerInterface
     */
    private ObjectManagerInterface $objectManager;

    /**
     * Pull this from config
     *
     * @var array
     */
    private array $types = [
        'files' => Files::class,
        'db' => Db::class
    ];

    public function __construct(Data $config, ObjectManagerInterface $objectManager)
    {
        $this->config = $config;
        $this->objectManager = $objectManager;
    }

    public function executeImportByName(string $importName)
    {
        if (!$this->config->hasImport($importName)) {
            throw new \InvalidArgumentException(
                sprintf('Cannot find configuration for import with name: "%s"', $importName)
            );
        }

        $type = $this->config->getImportType($importName);

        if (!isset($this->types[$type])) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Import configuration specified invalid type: "%s". Valid types are: "%s"',
                    $type,
                    implode(', ', array_keys($this->types))
                )
            );
        }

        $typeInstance = $this->objectManager->get($this->types[$type]);

        if (!$typeInstance instanceof Type) {
            throw new \RuntimeException(
                sprintf(
                    'Import type: "%s" does not implement require interface: "%s"',
                    get_class($typeInstance),
                    Type::class
                )
            );
        }

        return $typeInstance->run($this->config->getImportConfigByName($importName));
    }
}
