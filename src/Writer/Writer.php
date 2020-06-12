<?php

namespace Jh\Import\Writer;

use Jh\Import\Import\Record;
use Jh\Import\Import\Result;
use Jh\Import\Report\ReportItem;
use Jh\Import\Source\Source;

/**
 * @author Aydin Hassan <aydin@wearejh.com>
 */
interface Writer
{
    public function prepare(Source $source);

    public function write(Record $record, ReportItem $report);

    public function finish(Source $source): Result;
}
