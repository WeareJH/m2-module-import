<?php

namespace Jh\ImportTest\Report\Handler;

use Jh\Import\LogLevel;
use Jh\Import\Report\Handler\Email\Strategy\All;
use Jh\Import\Report\Handler\Email\Strategy\EmailHandlerStrategy;
use Jh\Import\Report\Handler\EmailHandler;
use Jh\Import\Report\Message;
use Jh\Import\Report\Report;
use Jh\Import\Report\ReportItem;
use Magento\Framework\App\Area;
use Magento\Framework\Mail\AddressFactory;
use Magento\Framework\Mail\EmailMessageInterfaceFactory;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\Mail\TransportInterface;
use Magento\Store\Model\Store;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;

class EmailHandlerTest extends TestCase
{
    use ProphecyTrait;

    public function testEmailIsSentAndWithCorrectData(): void
    {
        $transportBuilder = $this->prophesize(TransportBuilder::class);
        $transport = $this->prophesize(TransportInterface::class);

        $subject  = 'A problem occurred with import: "product" started on: "23-12-2019 10:00:03" ';
        $subject .= 'and finished on: "23-12-2019 11:00:03"';

        $transportBuilder
            ->setTemplateIdentifier('jh_import_import_report')
            ->willReturn($transportBuilder)
            ->shouldBeCalled();

        $transportBuilder
            ->setTemplateOptions(['area' => Area::AREA_FRONTEND, 'store' => Store::DEFAULT_STORE_ID])
            ->willReturn($transportBuilder)
            ->shouldBeCalled();

        $transportBuilder
            ->setTemplateVars(Argument::that(function (array $args) use ($subject) {
                return $args['subject'] === $subject
                    && strpos($args['content'], 'All import errors and messages are included') !== false;
            }))
            ->willReturn($transportBuilder)
            ->shouldBeCalled();

        $transportBuilder
            ->setFromByScope(['email' => 'import@wearejh.com', 'name' => 'JH Import'])
            ->willReturn($transportBuilder)
            ->shouldBeCalled();

        $transportBuilder
            ->addTo('aydin@wearejh.com', 'Aydin Hassan')
            ->willReturn($transportBuilder)
            ->shouldBeCalled();

        $transportBuilder->getTransport()->willReturn($transport);


        $handler = new EmailHandler(
            $transportBuilder->reveal(),
            new All(),
            'import@wearejh.com',
            'JH Import',
            ['Aydin Hassan' => 'aydin@wearejh.com']
        );

        $report = new Report([], 'product', 'source-id');
        $handler->start($report, new \DateTime('23rd December 2019 10:00:03'));
        $handler->handleMessage(new Message(LogLevel::EMERGENCY, 'Debug Info'));
        $handler->handleItemMessage(
            new ReportItem([], 100, 'sku', 'PROD1'),
            new Message(LogLevel::EMERGENCY, 'Debug Info')
        );

        $handler->finish($report, new \DateTime('23rd December 2019 11:00:03'), 1024);

        $transport->sendMessage()->shouldHaveBeenCalled();
    }

    public function testEmailIsNotSentIfStrategyReturnsNoMessages(): void
    {
        $transportBuilder = $this->prophesize(TransportBuilder::class);
        $transport = $this->prophesize(TransportInterface::class);

        $strategy = $this->prophesize(EmailHandlerStrategy::class);
        $strategy->filterItemMessages(Argument::any())->willReturn([]);
        $strategy->filterImportMessages(Argument::any())->willReturn([]);

        $handler = new EmailHandler(
            $transportBuilder->reveal(),
            $strategy->reveal(),
            'import@wearejh.com',
            'JH Import',
            ['Aydin Hassan' => 'aydin@wearejh.com']
        );

        $report = new Report([], 'product', 'source-id');
        $handler->start($report, new \DateTime('23rd December 2019 10:00:03'));
        $handler->handleMessage(new Message(LogLevel::EMERGENCY, 'Debug Info'));
        $handler->handleItemMessage(
            new ReportItem([], 100, 'sku', 'PROD1'),
            new Message(LogLevel::EMERGENCY, 'Debug Info')
        );

        $handler->finish($report, new \DateTime('23rd December 2019 11:00:03'), 1024);

        $transportBuilder->getTransport()->shouldNotHaveBeenCalled();
        $transport->sendMessage()->shouldNotHaveBeenCalled();
    }

    public function testEmailIsNotSentIfNoRecipients(): void
    {
        $transportBuilder = $this->prophesize(TransportBuilder::class);
        $transport = $this->prophesize(TransportInterface::class);

        $strategy = $this->prophesize(EmailHandlerStrategy::class);
        $strategy->filterItemMessages(Argument::any())->willReturn([]);
        $strategy->filterImportMessages(Argument::any())->willReturn([]);

        $handler = new EmailHandler(
            $transportBuilder->reveal(),
            $strategy->reveal(),
            'import@wearejh.com',
            'JH Import'
        );

        $report = new Report([], 'product', 'source-id');
        $handler->start($report, new \DateTime('23rd December 2019 10:00:03'));
        $handler->handleMessage(new Message(LogLevel::EMERGENCY, 'Debug Info'));
        $handler->handleItemMessage(
            new ReportItem([], 100, 'sku', 'PROD1'),
            new Message(LogLevel::EMERGENCY, 'Debug Info')
        );

        $handler->finish($report, new \DateTime('23rd December 2019 11:00:03'), 1024);

        $transportBuilder->getTransport()->shouldNotHaveBeenCalled();
        $transport->sendMessage()->shouldNotHaveBeenCalled();
    }
}
