<?php

declare(strict_types=1);

namespace Jh\ImportTest\Report\Handler\Email\Strategy;

use DateTime;
use Jh\Import\LogLevel;
use Jh\Import\Report\Handler\Email\Strategy\FingersCrossed;
use Jh\Import\Report\Message;
use Jh\Import\Report\Report;
use Jh\Import\Report\ReportItem;
use PHPUnit\Framework\TestCase;

class FingersCrossedTest extends TestCase
{
    public function testRenderInfo(): void
    {
        $filter = new FingersCrossed(LogLevel::EMERGENCY);
        $html = $filter->renderInfo(
            new Report([], 'my-import', 'source-id'),
            new DateTime('20 April 2020 10:00:01'),
            new DateTime('20 April 2020 10:15:10'),
            2048
        );

        self::assertStringContainsString(
            'An error occurred with a severity level of at least: "EMERGENCY" so we sent all messages after that',
            $html
        );

        self::assertMatchesRegularExpression('/Import Name:\s+my-import/', strip_tags($html));
        self::assertMatchesRegularExpression('/Source ID:\s+source-id/', strip_tags($html));
        self::assertMatchesRegularExpression('/Import Started:\s+20-04-2020 10:00:01/', strip_tags($html));
        self::assertMatchesRegularExpression('/Import Finished:\s+20-04-2020 10:15:10/', strip_tags($html));
        self::assertMatchesRegularExpression('/Peak Memory Usage:\s+2 KB/', strip_tags($html));
    }

    public function testFilterItemMessagesWithNoMessagesAboveMinimumLogLevel(): void
    {
        $messages = [
            [new ReportItem([], '100', 'sku', '200'), new Message(LogLevel::NOTICE, 'Notice 1')],
            [new ReportItem([], '101', 'sku', '200'), new Message(LogLevel::NOTICE, 'Notice 2')],
            [new ReportItem([], '102', 'sku', '200'), new Message(LogLevel::NOTICE, 'Notice 3')],
            [new ReportItem([], '103', 'sku', '200'), new Message(LogLevel::NOTICE, 'Notice 4')],
        ];

        $filter = new FingersCrossed(LogLevel::EMERGENCY);
        self::assertEmpty($filter->filterItemMessages($messages));
    }

    public function testFilterItemMessagesWithMessagesSameLevelAsMinimumLogLevel(): void
    {
        $messages = [
            [new ReportItem([], '100', 'sku', '200'), new Message(LogLevel::NOTICE, 'Notice 1')],
            [new ReportItem([], '101', 'sku', '200'), new Message(LogLevel::NOTICE, 'Notice 2')],
            [new ReportItem([], '102', 'sku', '200'), new Message(LogLevel::NOTICE, 'Notice 3')],
            [new ReportItem([], '103', 'sku', '200'), new Message(LogLevel::NOTICE, 'Notice 4')],
        ];

        $filter = new FingersCrossed(LogLevel::NOTICE);
        self::assertCount(4, $filter->filterItemMessages($messages));
    }

    public function testFilterItemMessagesPassesEveryMessageAfterMessageAboveMinimumLevel(): void
    {
        $messages = [
            [new ReportItem([], '100', 'sku', '200'), new Message(LogLevel::NOTICE, 'Message 1')],
            [new ReportItem([], '101', 'sku', '200'), new Message(LogLevel::NOTICE, 'Message 2')],
            [new ReportItem([], '102', 'sku', '200'), new Message(LogLevel::CRITICAL, 'Message 3')],
            [new ReportItem([], '103', 'sku', '200'), new Message(LogLevel::NOTICE, 'Message 4')],
        ];

        $filter = new FingersCrossed(LogLevel::CRITICAL);
        self::assertCount(2, $filter->filterItemMessages($messages));
    }

    public function testFilterImportMessagesWithNoMessagesAboveMinimumLogLevel(): void
    {
        $messages = [
            new Message(LogLevel::NOTICE, 'Notice 1'),
            new Message(LogLevel::NOTICE, 'Notice 2'),
            new Message(LogLevel::NOTICE, 'Notice 3'),
            new Message(LogLevel::NOTICE, 'Notice 4'),
        ];

        $filter = new FingersCrossed(LogLevel::EMERGENCY);
        self::assertEmpty($filter->filterImportMessages($messages));
    }

    public function testFilterImportMessagesWithMessagesSameLevelAsMinimumLogLevel(): void
    {
        $messages = [
            new Message(LogLevel::NOTICE, 'Notice 1'),
            new Message(LogLevel::NOTICE, 'Notice 2'),
            new Message(LogLevel::NOTICE, 'Notice 3'),
            new Message(LogLevel::NOTICE, 'Notice 4'),
        ];

        $filter = new FingersCrossed(LogLevel::NOTICE);
        self::assertCount(4, $filter->filterImportMessages($messages));
    }

    public function testFilterImportMessagesPassesEveryMessageAfterMessageAboveMinimumLevel(): void
    {
        $messages = [
            new Message(LogLevel::NOTICE, 'Message 1'),
            new Message(LogLevel::NOTICE, 'Message 2'),
            new Message(LogLevel::CRITICAL, 'Message 3'),
            new Message(LogLevel::NOTICE, 'Message 4'),
        ];

        $filter = new FingersCrossed(LogLevel::CRITICAL);
        self::assertCount(2, $filter->filterImportMessages($messages));
    }
}
