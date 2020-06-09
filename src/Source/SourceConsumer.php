<?php

declare(strict_types=1);

namespace Jh\Import\Source;

use Jh\Import\Config;
use Jh\Import\Import\Record;
use Jh\Import\Report\Report;

class SourceConsumer
{
    /**
     * @return Record[]
     */
    public function toArray(Source $source, Config $config) : array
    {
        $data = [];

        $source->traverse(
            function (int $rowNumber, array $row) use (&$data) {
                $data[] = new Record($rowNumber, $row);
            },
            function ($rowNumber) {
                //noop - error reading/parsing row
            },
            new Report([], $config->getImportName(), $source->getSourceId())
        );

        return $data;
    }
}
