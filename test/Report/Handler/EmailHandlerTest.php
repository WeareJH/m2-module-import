<?php

namespace Jh\ImportTest\Report\Handler;

use Jh\Import\LogLevel;
use Jh\Import\Report\Handler\EmailHandler;
use Jh\Import\Report\Message;
use Jh\Import\Report\Report;
use Jh\Import\Report\ReportItem;
use Magento\Framework\Mail\TransportInterface;
use Magento\Framework\Mail\TransportInterfaceFactory;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;

class EmailHandlerTest extends TestCase
{
    private $transportFactory;

    public function setUp()
    {
        $this->transportFactory = $this->prophesize(TransportInterfaceFactory::class);
    }

    public function testNoEmailIsSentIfNoMessageAboveCertainLevel()
    {
        $handler = new EmailHandler(
            $this->transportFactory->reveal(),
            ['aydin@wearejh.com'],
            'import@wearejh.com',
            LogLevel::EMERGENCY
        );

        $handler->handleMessage(new Message(LogLevel::DEBUG, 'Debug Info'));
        $handler->handleItemMessage(
            new ReportItem([], 100, 'sku', 'PROD1'),
            new Message(LogLevel::DEBUG, 'Debug Info')
        );

        $handler->finish(new Report([], 'product', 'source-id'), new \DateTime, 1024);

        $this->transportFactory->create(Argument::type('array'))->shouldNotHaveBeenCalled();
    }

    public function testEmailSentIfMessageAboveCertainLevel()
    {
        $handler = new EmailHandler(
            $this->transportFactory->reveal(),
            ['aydin@wearejh.com'],
            'import@wearejh.com',
            LogLevel::EMERGENCY
        );

        $report = new Report([], 'product', 'source-id');
        $handler->start($report, new \DateTime);
        $handler->handleMessage(new Message(LogLevel::EMERGENCY, 'Debug Info'));
        $handler->handleItemMessage(
            new ReportItem([], 100, 'sku', 'PROD1'),
            new Message(LogLevel::EMERGENCY, 'Debug Info')
        );

        $transport = $this->prophesize(TransportInterface::class)->reveal();
        $this->transportFactory->create(Argument::type('array'))->willReturn($transport)->shouldBeCalledTimes(1);

        $handler->finish($report, new \DateTime, 1024);
    }

    public function testEmailIsOnlySendDuringFinish()
    {
        $handler = new EmailHandler(
            $this->transportFactory->reveal(),
            ['aydin@wearejh.com'],
            'import@wearejh.com',
            LogLevel::EMERGENCY
        );

        $report = new Report([], 'product', 'source-id');
        $handler->start($report, new \DateTime);
        $handler->handleMessage(new Message(LogLevel::EMERGENCY, 'Debug Info'));
        $handler->handleItemMessage(
            new ReportItem([], 100, 'sku', 'PROD1'),
            new Message(LogLevel::EMERGENCY, 'Debug Info')
        );

        $this->transportFactory->create(Argument::type('array'))->shouldNotBeCalled();
    }

    public function testEmailMetaCorrect()
    {
        $handler = new EmailHandler(
            $this->transportFactory->reveal(),
            ['aydin@wearejh.com'],
            'import@wearejh.com',
            LogLevel::ERROR
        );

        $report = new Report([], 'product', 'source-id');
        $handler->start($report, new \DateTime('April 12 2017 10:00'));
        $handler->handleMessage(new Message(LogLevel::EMERGENCY, 'A super serious emergency message'));
        $handler->handleItemMessage(
            new ReportItem([], 100, 'sku', 'PROD1'),
            new Message(LogLevel::ERROR, 'A dangerous error occurred')
        );

        $transport = $this->prophesize(TransportInterface::class)->reveal();

        $mailVerifier = function ($data) {
            if (!is_array($data)) {
                return false;
            }

            /** @var \Magento\Framework\Mail\Message $mail */
            $mail = $data['message'];

            if ($mail->getRecipients() !== ['aydin@wearejh.com']) {
                return false;
            }

            if ($mail->getFrom() !== 'import@wearejh.com') {
                return false;
            }

            $subject  = 'A problem occurred with import: "product" started on: "12-04-2017 10:00:00" ';
            $subject .= 'and finished on: "13-04-2017 09:21:00"';
            if ($mail->getSubject() !== $subject) {
                return false;
            }

            return true;
        };

        $this->transportFactory->create(Argument::that($mailVerifier))->willReturn($transport)->shouldBeCalledTimes(1);

        $handler->finish($report, new \DateTime('April 13 2017 09:21'), 1024);
    }

    public function testEmailContainsAllMessages()
    {
        $handler = new EmailHandler(
            $this->transportFactory->reveal(),
            ['aydin@wearejh.com'],
            'import@wearejh.com',
            LogLevel::ERROR
        );

        $report = new Report([], 'product', 'source-id');
        $handler->start($report, new \DateTime('April 12 2017 10:00'));
        $handler->handleMessage(new Message(LogLevel::EMERGENCY, 'A super serious emergency message'));
        $handler->handleItemMessage(
            new ReportItem([], 100, 'sku', 'PROD1'),
            new Message(LogLevel::ERROR, 'A dangerous error occurred')
        );

        $transport = $this->prophesize(TransportInterface::class)->reveal();

        $mailVerifier = function ($data) {
            if (!is_array($data)) {
                return false;
            }

            /** @var \Magento\Framework\Mail\Message $mail */
            $content = $data['message']->getBody()->getRawContent();

            self::assertContains('A super serious emergency message', $content);
            self::assertContains('A dangerous error occurred', $content);

            return true;
        };

        $this->transportFactory->create(Argument::that($mailVerifier))->willReturn($transport)->shouldBeCalledTimes(1);

        $handler->finish($report, new \DateTime('April 13 2017 09:21'), 1024);
    }
}
