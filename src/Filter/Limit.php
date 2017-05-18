<?php

namespace Jh\Import\Filter;

use Jh\Import\Import\Record;

/**
 * @author Aydin Hassan <aydin@wearejh.com>
 */
class Limit
{
    /**
     * @var int
     */
    private $limit;

    /**
     * @param int $limit
     */
    public function __construct(int $limit = 100)
    {
        $this->limit = $limit;
    }

    public function __invoke(Record $record)
    {
        return $record->getRowNumber() <= $this->limit;
    }
}
