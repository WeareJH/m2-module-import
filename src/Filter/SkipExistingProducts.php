<?php

namespace Jh\Import\Filter;

use Jh\Import\Import\Record;
use Magento\Framework\App\ResourceConnection;

class SkipExistingProducts
{
    /**
     * @var array
     */
    private $existingSkus = [];

    public function __construct(ResourceConnection $connection)
    {
        $select = $connection->getConnection()
            ->select()
            ->from('catalog_product_entity', ['sku']);

        $this->existingSkus = $connection->getConnection()->fetchCol($select);
    }

    public function __invoke(Record $record): bool
    {
        return !in_array($record->getColumnValue('sku'), $this->existingSkus, true);
    }
}
