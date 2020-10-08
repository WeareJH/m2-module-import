<?php

namespace Jh\Import\Writer;

use Jh\Import\Import\Record;
use Jh\Import\Import\Result;
use Jh\Import\Report\ReportItem;
use Jh\Import\Source\Source;
use Jh\Import\Config;

/**
 * @author Aydin Hassan <aydin@wearejh.com>
 */
class CollectingWriter implements Writer
{
    /**
     * @var array
     */
    private $data = [];

    private $affectedIds = [];

    public function prepare(Source $source, Config $config)
    {
        // noop
    }

    public function write(Record $record, ReportItem $report)
    {
        $this->data[] = $record->asArray();
    }

    public function finish(Source $source): Result
    {
        return new Result($this->affectedIds);
    }

    public function getData()
    {
        return $this->data;
    }

    public function setAffectedIds(array $ids)
    {
        $this->affectedIds = $ids;
    }
}
