<?php

declare(strict_types=1);

namespace Jh\ImportTest\Report\Handler\Email;

use Jh\Import\LogLevel;
use Jh\Import\Report\Handler\Email\Renderer;
use Jh\Import\Report\Message;
use Jh\Import\Report\ReportItem;
use PHPUnit\Framework\TestCase;

class RendererTest extends TestCase
{
    public function testTitle(): void
    {
        self::assertEquals(
            '<h1 style="background: #000000;color: #ffffff;padding: 5px;" class="monolog-output">Some title</h1>',
            Renderer::title('Some title')
        );

        self::assertEquals(
            '<h2 style="background: #000000;color: #ffffff;padding: 5px;" class="monolog-output">Some title</h2>',
            Renderer::title('Some title', 2)
        );

        self::assertEquals(
            '<h3 style="background: #000000;color: #ffffff;padding: 5px;" class="monolog-output">Some title</h3>',
            Renderer::title('Some title', 3)
        );
    }

    public function testLogTitle(): void
    {
        self::assertEquals(
            '<h3 style="background: #cccccc;color: #ffffff;padding: 5px;" class="monolog-output">DEBUG</h3>',
            Renderer::logTitle(LogLevel::DEBUG)
        );
        self::assertEquals(
            '<h3 style="background: #468847;color: #ffffff;padding: 5px;" class="monolog-output">INFO</h3>',
            Renderer::logTitle(LogLevel::INFO)
        );
        self::assertEquals(
            '<h3 style="background: #3a87ad;color: #ffffff;padding: 5px;" class="monolog-output">NOTICE</h3>',
            Renderer::logTitle(LogLevel::NOTICE)
        );
        self::assertEquals(
            '<h3 style="background: #c09853;color: #ffffff;padding: 5px;" class="monolog-output">WARNING</h3>',
            Renderer::logTitle(LogLevel::WARNING)
        );
        self::assertEquals(
            '<h3 style="background: #f0ad4e;color: #ffffff;padding: 5px;" class="monolog-output">ERROR</h3>',
            Renderer::logTitle(LogLevel::ERROR)
        );
        self::assertEquals(
            '<h3 style="background: #FF7708;color: #ffffff;padding: 5px;" class="monolog-output">CRITICAL</h3>',
            Renderer::logTitle(LogLevel::CRITICAL)
        );
        self::assertEquals(
            '<h3 style="background: #C12A19;color: #ffffff;padding: 5px;" class="monolog-output">ALERT</h3>',
            Renderer::logTitle(LogLevel::ALERT)
        );
        self::assertEquals(
            '<h3 style="background: #000000;color: #ffffff;padding: 5px;" class="monolog-output">EMERGENCY</h3>',
            Renderer::logTitle(LogLevel::EMERGENCY)
        );
    }

    public function testTableRow(): void
    {
        $expected  = "<tr style=\"padding: 4px;spacing: 0;text-align: left;\">";
        $expected .= "    <th style=\"background: #cccccc\"; width=\"150px\">Field:</th>";
        $expected .= "    <td style=\"padding: 4px;spacing: 0;text-align:left;background: #eeeeee\">value</td>";
        $expected .= "</tr>";

        self::assertEquals($expected, Renderer::tableRow('Field', 'value'));
    }

    public function testItemLogEntry(): void
    {
        $reportItem = new ReportItem([], '100', 'sku', 'PROD1');
        $message = new Message(LogLevel::CRITICAL, 'Some critical error', new \DateTime('20 Apr 2020 10:00:00'));

        $html = Renderer::itemLogEntry($reportItem, $message);

        self::assertStringContainsString(
            '<h3 style="background: #FF7708;color: #ffffff;padding: 5px;" class="monolog-output">CRITICAL</h3>',
            $html
        );
        self::assertMatchesRegularExpression('/Reference Line:\s+100/', strip_tags($html));
        self::assertMatchesRegularExpression('/ID Field:\s+sku/', strip_tags($html));
        self::assertMatchesRegularExpression('/ID Value:\s+PROD1/', strip_tags($html));
        self::assertMatchesRegularExpression('/Message:\s+Some critical error/', strip_tags($html));
        self::assertMatchesRegularExpression('/Time:\s+20-04-2020 10:00:00/', strip_tags($html));
    }

    public function testImportLogEntry(): void
    {
        $message = new Message(LogLevel::CRITICAL, 'Some critical error', new \DateTime('20 Apr 2020 10:00:00'));

        $html = Renderer::importLogEntry($message);

        self::assertStringContainsString(
            '<h3 style="background: #FF7708;color: #ffffff;padding: 5px;" class="monolog-output">CRITICAL</h3>',
            $html
        );
        self::assertMatchesRegularExpression('/Message:\s+Some critical error/', strip_tags($html));
        self::assertMatchesRegularExpression('/Time:\s+20-04-2020 10:00:00/', strip_tags($html));
    }
}
