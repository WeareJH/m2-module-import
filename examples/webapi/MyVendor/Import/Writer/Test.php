<?php

namespace MyVendor\Import\Writer;

use Jh\Import\Config;
use Jh\Import\Import\Record;
use Jh\Import\Import\Result;
use Jh\Import\Report\ReportItem;
use Jh\Import\Source\Source;
use Jh\Import\Writer\Writer;

class Test implements Writer
{
    public function prepare(Source $source, Config $config)
    {
    }

    public function write(Record $record, ReportItem $report)
    {
    }

    public function finish(Source $source): Result
    {
        return new Result([]);
    }
}
