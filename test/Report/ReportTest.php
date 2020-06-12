<?php

namespace Jh\ImportTest\Report;

use Jh\Import\Report\CollectingReport;
use Jh\Import\Report\Handler\CollectingHandler;
use Jh\Import\Report\Handler\ConsoleHandler;
use Jh\Import\Report\Report;
use Jh\Import\Report\ReportItem;
use PHPUnit\Framework\TestCase;

/**
 * @author Aydin Hassan <aydin@wearejh.com>
 */
class ReportTest extends TestCase
{
    public function testAddersAndGetters()
    {
        $report = new Report([$handler = new CollectingHandler()], 'product', 'some-source-id');

        self::assertEmpty($handler->messages);
        self::assertEquals('product', $report->getImportName());
        self::assertEquals('some-source-id', $report->getSourceId());

        $report->addError('Error 1');
        $report->addWarning('Warning 1');

        self::assertCount(2, $handler->messages);

        self::assertEquals(
            [
                [
                    'log_level' => 'ERROR',
                    'message'  => 'Error 1',
                ],
                [
                    'log_level' => 'WARNING',
                    'message'  => 'Warning 1',
                ]
            ],
            $handler->messages
        );
    }

    public function testReportIsSuccessfulIfNoErrors()
    {
        $report = new Report([], 'product', 'some-source-id');
        self::assertTrue($report->isSuccessful());
    }

    public function testReportIsUnsuccessfulIfErrors()
    {
        $report = new Report([], 'product', 'some-source-id');

        self::assertTrue($report->isSuccessful());

        $report->addError('Error 1');
        self::assertFalse($report->isSuccessful());
    }

    public function testReportIsSuccessfulIfNoErrorsAndSomeWarnings()
    {
        $report = new Report([], 'product', 'some-source-id');

        self::assertTrue($report->isSuccessful());

        $report->addWarning('Error 1');
        self::assertTrue($report->isSuccessful());
    }

    public function testNewItem()
    {
        $report = new Report([], 'product', 'some-source-id');

        $item = $report->newItem(100, 'sku', 'PROD1');

        self::assertInstanceOf(ReportItem::class, $item);
        self::assertEquals(100, $item->getReferenceLine());
        self::assertEquals('sku', $item->getIdField());
        self::assertEquals('PROD1', $item->getIdValue());
        self::assertSame([$item], $report->getItems());
    }
}
