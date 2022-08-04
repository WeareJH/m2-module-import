<?php

declare(strict_types=1);

namespace Jh\ImportTest\Report\Handler\Email\Strategy;

use DateTime;
use Jh\Import\LogLevel;
use Jh\Import\Report\Handler\Email\Strategy\All;
use Jh\Import\Report\Message;
use Jh\Import\Report\Report;
use Jh\Import\Report\ReportItem;
use PHPUnit\Framework\TestCase;

class AllTest extends TestCase
{
    public function testRenderInfo(): void
    {
        $filter = new All();
        $html = $filter->renderInfo(
            new Report([], 'my-import', 'source-id'),
            new DateTime('20 April 2020 10:00:01'),
            new DateTime('20 April 2020 10:15:10'),
            2048
        );

        self::assertStringContainsString(
            'All import errors and messages are included',
            $html
        );

        self::assertMatchesRegularExpression('/Import Name:\s+my-import/', strip_tags($html));
        self::assertMatchesRegularExpression('/Source ID:\s+source-id/', strip_tags($html));
        self::assertMatchesRegularExpression('/Import Started:\s+20-04-2020 10:00:01/', strip_tags($html));
        self::assertMatchesRegularExpression('/Import Finished:\s+20-04-2020 10:15:10/', strip_tags($html));
        self::assertMatchesRegularExpression('/Peak Memory Usage:\s+2 KB/', strip_tags($html));
    }

    public function testFilterItemMessagesReturnsAllMessages(): void
    {
        $messages = [
            [new ReportItem([], '100', 'sku', '200'), new Message(LogLevel::NOTICE, 'Notice 1')],
            [new ReportItem([], '101', 'sku', '200'), new Message(LogLevel::NOTICE, 'Notice 2')],
            [new ReportItem([], '102', 'sku', '200'), new Message(LogLevel::NOTICE, 'Notice 3')],
            [new ReportItem([], '103', 'sku', '200'), new Message(LogLevel::NOTICE, 'Notice 4')],
        ];

        $filter = new All();
        self::assertCount(4, $filter->filterItemMessages($messages));
    }

    public function testFilterImportMessagesReturnsAllMessages(): void
    {
        $messages = [
            [new Message(LogLevel::NOTICE, 'Notice 1')],
            [new Message(LogLevel::NOTICE, 'Notice 2')],
            [new Message(LogLevel::NOTICE, 'Notice 3')],
            [new Message(LogLevel::NOTICE, 'Notice 4')],
        ];

        $filter = new All();
        self::assertCount(4, $filter->filterImportMessages($messages));
    }
}
