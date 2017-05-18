<?php

namespace Jh\Import\Transformer;

use Jh\Import\Import\Record;
use Magento\Catalog\Model\Product\Attribute\Source\Status;

/**
 * @author Aydin Hassan <aydin@wearejh.com>
 */
class ProductStatusTransformer
{
    private $map = [
        Status::STATUS_ENABLED => [
            'Enabled', 'enabled', 'yes', 1, '1', 'true', 'Yes', 'True'
        ],
        Status::STATUS_DISABLED => [
            'Disabled', 'disabled', 'no', 0, '0', 'false', 'No', 'False'
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

    /**
     * @param Record $record
     * @return void
     */
    public function __invoke(Record $record)
    {
        $record->transform($this->column, function ($columnValue) {
            //if the value looks like a truthy value - return enabled
            if (in_array($columnValue, $this->map[Status::STATUS_ENABLED], true)) {
                return Status::STATUS_ENABLED;
            }

            //else assume it look likes a falsey value or it is just an invalid value which
            //we default to disabled with anyway
            return Status::STATUS_DISABLED;
        });
    }
}
