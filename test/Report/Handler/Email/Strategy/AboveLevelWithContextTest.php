<?php

declare(strict_types=1);

namespace Jh\ImportTest\Report\Handler\Email\Strategy;

use Jh\Import\LogLevel;
use Jh\Import\Report\Handler\Email\Strategy\AboveLevelWithContext;
use Jh\Import\Report\Message;
use Jh\Import\Report\ReportItem;
use PHPUnit\Framework\TestCase;

class AboveLevelWithContextTest extends TestCase
{
    public function testFilterItemMessages(): void
    {
        $filter = new AboveLevelWithContext(LogLevel::ERROR, 20);
        $messages = [
            $l01 = [new ReportItem([], '100', 'sku', '200'), new Message(LogLevel::NOTICE, 'Notice 1')],
            $l02 = [new ReportItem([], '101', 'sku', '201'), new Message(LogLevel::NOTICE, 'Notice 2')],
            $l03 = [new ReportItem([], '102', 'sku', '202'), new Message(LogLevel::NOTICE, 'Notice 3')],
            $l04 = [new ReportItem([], '103', 'sku', '203'), new Message(LogLevel::NOTICE, 'Notice 4')],
            $l05 = [new ReportItem([], '104', 'sku', '204'), new Message(LogLevel::NOTICE, 'Notice 5')],
            $l06 = [new ReportItem([], '105', 'sku', '205'), new Message(LogLevel::NOTICE, 'Notice 6')],
            $l07 = [new ReportItem([], '106', 'sku', '206'), new Message(LogLevel::NOTICE, 'Notice 7')],
            $l08 = [new ReportItem([], '107', 'sku', '207'), new Message(LogLevel::NOTICE, 'Notice 8')],
            $l09 = [new ReportItem([], '108', 'sku', '208'), new Message(LogLevel::NOTICE, 'Notice 9')],
            $l10 = [new ReportItem([], '109', 'sku', '209'), new Message(LogLevel::NOTICE, 'Notice 10')],
            $l11 = [new ReportItem([], '110', 'sku', '210'), new Message(LogLevel::CRITICAL, 'Critical 1')],
            $l12 = [new ReportItem([], '111', 'sku', '211'), new Message(LogLevel::NOTICE, 'Notice 11')],
            $l13 = [new ReportItem([], '112', 'sku', '212'), new Message(LogLevel::NOTICE, 'Notice 12')],
            $l14 = [new ReportItem([], '113', 'sku', '213'), new Message(LogLevel::NOTICE, 'Notice 13')],
            $l15 = [new ReportItem([], '114', 'sku', '214'), new Message(LogLevel::NOTICE, 'Notice 14')],
            $l16 = [new ReportItem([], '115', 'sku', '215'), new Message(LogLevel::NOTICE, 'Notice 15')],
            $l17 = [new ReportItem([], '116', 'sku', '216'), new Message(LogLevel::NOTICE, 'Notice 16')],
        ];

        $expected = [
            $l06, $l07, $l08, $l09, $l10, $l11, $l12, $l13, $l14, $l15, $l16
        ];

        self::assertEquals(
            $expected,
            $filter->filterItemMessages($messages)
        );
    }

    public function testFilterImportMessages(): void
    {
        $filter = new AboveLevelWithContext(LogLevel::ERROR, 20);
        $messages = [
            $l01 = new Message(LogLevel::NOTICE, 'Notice 1'),
            $l02 = new Message(LogLevel::NOTICE, 'Notice 2'),
            $l03 = new Message(LogLevel::NOTICE, 'Notice 3'),
            $l04 = new Message(LogLevel::NOTICE, 'Notice 4'),
            $l05 = new Message(LogLevel::NOTICE, 'Notice 5'),
            $l06 = new Message(LogLevel::NOTICE, 'Notice 6'),
            $l07 = new Message(LogLevel::NOTICE, 'Notice 7'),
            $l08 = new Message(LogLevel::NOTICE, 'Notice 8'),
            $l09 = new Message(LogLevel::NOTICE, 'Notice 9'),
            $l10 = new Message(LogLevel::NOTICE, 'Notice 10'),
            $l11 = new Message(LogLevel::CRITICAL, 'Critical 1'),
            $l12 = new Message(LogLevel::NOTICE, 'Notice 11'),
            $l13 = new Message(LogLevel::NOTICE, 'Notice 12'),
            $l14 = new Message(LogLevel::NOTICE, 'Notice 13'),
            $l15 = new Message(LogLevel::NOTICE, 'Notice 14'),
            $l16 = new Message(LogLevel::NOTICE, 'Notice 15'),
            $l17 = new Message(LogLevel::NOTICE, 'Notice 16'),
        ];

        $expected = [
            $l06, $l07, $l08, $l09, $l10, $l11, $l12, $l13, $l14, $l15, $l16
        ];

        self::assertEquals(
            $expected,
            $filter->filterImportMessages($messages)
        );
    }
}
