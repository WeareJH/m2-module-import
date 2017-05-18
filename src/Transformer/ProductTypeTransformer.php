<?php

namespace Jh\Import\Transformer;

use Jh\Import\Import\Record;
use Jh\Import\Report\ReportItem;
use Magento\Catalog\Model\Product\Type;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;

/**
 * @author Aydin Hassan <aydin@wearejh.com>
 */
class ProductTypeTransformer
{
    private $map = [
        Type::TYPE_SIMPLE => [
            'simple', 'Simple'
        ],
        Configurable::TYPE_CODE => [
            'configurable', 'Configurable'
        ]
    ];

    /**
     * @var string
     */
    private $column;

    public function __construct(string $column)
    {
        $this->column = $column;
    }

    public function __invoke(Record $record, ReportItem $reportItem)
    {
        $record->transform($this->column, function ($type) use ($reportItem) {
            if (in_array($type, $this->map[Type::TYPE_SIMPLE], true)) {
                return Type::TYPE_SIMPLE;
            }

            if (in_array($type, $this->map[Configurable::TYPE_CODE], true)) {
                return Configurable::TYPE_CODE;
            }

            $reportItem->addWarning(sprintf('Product Type "%s" is invalid and/or not supported.', $type));
            return null;
        });
    }
}
