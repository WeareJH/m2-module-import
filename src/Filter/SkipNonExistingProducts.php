<?php

namespace Jh\Import\Filter;

use Jh\Import\Import\Record;
use Magento\Framework\App\ResourceConnection;

/**
 * @author Aydin Hassan <aydin@wearejh.com>
 */
class SkipNonExistingProducts
{
    /**
     * @var array
     */
    private $existingSkus = [];

    /**
     * @var string
     */
    private $skuField;

    public function __construct(ResourceConnection $connection, string $skuField = 'sku')
    {
        $select = $connection->getConnection()
            ->select()
            ->from('catalog_product_entity', ['sku']);

        $this->existingSkus = $connection->getConnection()->fetchCol($select);
        $this->skuField = $skuField;
    }

    public function __invoke(Record $record)
    {
        return in_array($record->getColumnValue($this->skuField), $this->existingSkus, true);
    }
}
