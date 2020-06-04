<?php

declare(strict_types=1);

namespace Jh\Import\Filter;

use Jh\Import\Import\Record;
use Jh\Import\Report\ReportItem;

class LoggingSkipNonExistingProducts
{
    /**
     * @var SkipNonExistingProducts
     */
    private $skipNonExistingProducts;

    /**
     * @var string
     */
    private $skuField;

    public function __construct(SkipNonExistingProducts $skipNonExistingProducts, string $skuField = 'sku')
    {
        $this->skipNonExistingProducts = $skipNonExistingProducts;
        $this->skuField = $skuField;
    }

    public function __invoke(Record $record, ReportItem $reportItem) : bool
    {
        $found = $this->skipNonExistingProducts->__invoke($record);

        if (!$found) {
            //TODO: Make level configurable so we can cause import to fail
            //warning will not cause a fail, whereas errror will
            $reportItem->addWarning(sprintf('Product: "%s" does not exist.', $record->getColumnValue($this->skuField)));
        }

        return $found;
    }
}
