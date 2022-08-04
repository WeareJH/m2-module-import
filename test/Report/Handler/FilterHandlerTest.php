<?php

namespace Jh\ImportTest\Report\Handler;

use Jh\Import\LogLevel;
use Jh\Import\Report\Handler\FilterHandler;
use Jh\Import\Report\Handler\Handler;
use Jh\Import\Report\Message;
use Jh\Import\Report\Report;
use Jh\Import\Report\ReportItem;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;

class FilterHandlerTest extends TestCase
{
    use ProphecyTrait;

    public function testStartDelegatesToWrapped(): void
    {
        $wrapped = $this->prophesize(Handler::class);

        $filter = new FilterHandler(LogLevel::CRITICAL, $wrapped->reveal());
        $report = new Report([], 'product', 'source-id');
        $startTime = new \DateTime();
        $filter->start($report, $startTime);

        $wrapped->start($report, $startTime)->shouldHaveBeenCalled();
    }

    public function testFinishDelegatesToWrapped(): void
    {
        $wrapped = $this->prophesize(Handler::class);

        $filter = new FilterHandler(LogLevel::CRITICAL, $wrapped->reveal());
        $report = new Report([], 'product', 'source-id');
        $finishTime = new \DateTime();
        $filter->finish($report, $finishTime, 1024);

        $wrapped->finish($report, $finishTime, 1024)->shouldHaveBeenCalled();
    }

    public function testMessageWithLowerLogLevelIsNotPassedToWrappedHandler(): void
    {
        $wrapped = $this->prophesize(Handler::class);

        $message = new Message(LogLevel::DEBUG, 'Debug info');
        $item = new ReportItem([], 100, 'sku', 'PROD1');
        $itemMessage = new Message(LogLevel::DEBUG, 'Debug info');

        $filter = new FilterHandler(LogLevel::CRITICAL, $wrapped->reveal());
        $filter->handleMessage($message);
        $filter->handleItemMessage($item, $itemMessage);

        $wrapped->handleMessage($message)->shouldNotHaveBeenCalled();
        $wrapped->handleItemMessage($item, $itemMessage)->shouldNotHaveBeenCalled();
    }

    public function testMessageWithSameLogLevelIsPassedToWrappedHandler(): void
    {
        $wrapped = $this->prophesize(Handler::class);

        $message = new Message(LogLevel::CRITICAL, 'Debug info');
        $item = new ReportItem([], 100, 'sku', 'PROD1');
        $itemMessage = new Message(LogLevel::CRITICAL, 'Debug info');

        $filter = new FilterHandler(LogLevel::CRITICAL, $wrapped->reveal());
        $filter->handleMessage($message);
        $filter->handleItemMessage($item, $itemMessage);

        $wrapped->handleMessage($message)->shouldHaveBeenCalled();
        $wrapped->handleItemMessage($item, $itemMessage)->shouldHaveBeenCalled();
    }

    public function testMessageWithHigherLogLevelIsPassedToWrappedHandler(): void
    {
        $wrapped = $this->prophesize(Handler::class);

        $message = new Message(LogLevel::EMERGENCY, 'Debug info');
        $item = new ReportItem([], 100, 'sku', 'PROD1');
        $itemMessage = new Message(LogLevel::EMERGENCY, 'Debug info');

        $filter = new FilterHandler(LogLevel::CRITICAL, $wrapped->reveal());
        $filter->handleMessage($message);
        $filter->handleItemMessage($item, $itemMessage);

        $wrapped->handleMessage($message)->shouldHaveBeenCalled();
        $wrapped->handleItemMessage($item, $itemMessage)->shouldHaveBeenCalled();
    }
}
