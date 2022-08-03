<?php

namespace Jh\ImportTest\Writer;

use Jh\Import\Import\Record;
use Jh\Import\Report\ReportItem;
use Jh\Import\Writer\CollectingWriter;
use PHPUnit\Framework\TestCase;

/**
 * @author Aydin Hassan <aydin@wearejh.com>
 */
class CollectingWriterTest extends TestCase
{
    public function testCollectingWriter(): void
    {
        $record1 = new Record(1, ['col1' => 'row1col1value', 'col2' => 'row1col2value']);
        $record2 = new Record(2, ['col1' => 'row2col1value', 'col2' => 'row2col2value']);
        $report = new ReportItem([], 100, 'sku', 100);

        $writer = new CollectingWriter();
        $writer->write($record1, $report);
        $writer->write($record2, $report);

        self::assertEquals(
            [
                [
                    'col1' => 'row1col1value',
                    'col2' => 'row1col2value',
                ],
                [
                    'col1' => 'row2col1value',
                    'col2' => 'row2col2value',
                ]
            ],
            $writer->getData()
        );
    }
}
