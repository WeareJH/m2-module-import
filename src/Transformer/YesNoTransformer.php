<?php

namespace Jh\Import\Transformer;

use Jh\Import\Import\Record;

/**
 * @author Aydin Hassan <aydin@wearejh.com>
 */
class YesNoTransformer
{
    private $map = [
        'yes' => [
            'yes', 1, '1', 'true', 'Yes', 'True'
        ],
        'no'  => [
            'no', 0, '0', 'false', 'No', 'False'
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
            //if the value looks like a truthy value - return 1
            if (in_array($columnValue, $this->map['yes'], true)) {
                return 1;
            }

            //else assume it look likes a falsey value or it is just an invalid value which
            //we default to 0 with anyway
            return 0;
        });
    }
}
