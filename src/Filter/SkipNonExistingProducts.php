<?php

namespace Jh\Import\Filter;

use Jh\Import\Config;
use Jh\Import\Import\Record;
use Jh\Import\Import\RequiresPreparation;
use Magento\Framework\App\ResourceConnection;

class SkipNonExistingProducts implements RequiresPreparation
{
    /**
     * @var array
     */
    private $existingSkus = [];

    /**
     * @var string
     */
    private $skuField = 'sku';

    public function __construct(ResourceConnection $connection)
    {
        $select = $connection->getConnection()
            ->select()
            ->from('catalog_product_entity', ['sku']);

        $this->existingSkus = $connection->getConnection()->fetchCol($select);
    }

    public function prepare(Config $config): void
    {
        $this->skuField = $config->getIdField();
    }

    public function __invoke(Record $record): bool
    {
        return in_array($record->getColumnValue($this->skuField), $this->existingSkus, true);
    }
}
