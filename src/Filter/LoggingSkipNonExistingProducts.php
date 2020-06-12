<?php

declare(strict_types=1);

namespace Jh\Import\Filter;

use Jh\Import\Config;
use Jh\Import\Import\Record;
use Jh\Import\Import\RequiresPreparation;
use Jh\Import\Report\ReportItem;

class LoggingSkipNonExistingProducts implements RequiresPreparation
{
    /**
     * @var SkipNonExistingProducts
     */
    private $skipNonExistingProducts;

    /**
     * @var string
     */
    private $skuField = 'sku';

    public function __construct(SkipNonExistingProducts $skipNonExistingProducts)
    {
        $this->skipNonExistingProducts = $skipNonExistingProducts;
    }

    public function prepare(Config $config): void
    {
        $this->skuField = $config->getIdField();
    }

    public function __invoke(Record $record, ReportItem $reportItem): bool
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
