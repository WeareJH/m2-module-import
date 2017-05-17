<?php

namespace Jh\ImportTest\Report;

use Jh\Import\Report\Handler\CollectingHandler;
use Jh\Import\Report\Handler\ConsoleHandler;
use Jh\Import\Report\ReportItem;
use PHPUnit\Framework\TestCase;

/**
 * @author Aydin Hassan <aydin@wearejh.com>
 */
class ReportItemTest extends TestCase
{
    public function testAddersAndGetters()
    {
        $report = new ReportItem([$handler = new CollectingHandler], 33, 'sku', 'PROD1');

        self::assertEmpty($handler->itemMessages);
        self::assertEquals(33, $report->getReferenceLine());
        self::assertEquals('sku', $report->getIdField());
        self::assertEquals('PROD1', $report->getIdValue());

        $report->addError('Error 1');
        $report->addWarning('Warning 1');
        $report->addDebug('Debug 1');

        self::assertCount(3, $handler->itemMessages);
        self::assertEquals(
            [
                [
                    'log_level' => 'ERROR',
                    'message'   => 'Error 1',
                ],
                [
                    'log_level' => 'WARNING',
                    'message'   => 'Warning 1',
                ],
                [
                    'log_level' => 'DEBUG',
                    'message'   => 'Debug 1',
                ],
            ],
            $handler->itemMessages
        );
    }
}
