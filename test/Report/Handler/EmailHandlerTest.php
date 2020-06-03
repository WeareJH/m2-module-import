<?php

namespace Jh\ImportTest\Report\Handler;

use Jh\Import\LogLevel;
use Jh\Import\Report\Handler\EmailHandler;
use Jh\Import\Report\Message;
use Jh\Import\Report\Report;
use Jh\Import\Report\ReportItem;
use Jh\ImportTest\Mock\TransportBuilderMock;
use Jh\ImportTest\Mock\TransportMock;
use Jh\UnitTestHelpers\ObjectHelper;
use Magento\Framework\Mail\AddressConverter;
use Magento\Framework\Mail\AddressFactory;
use Magento\Framework\Mail\EmailMessage;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\Mail\TransportInterface;
use Magento\Framework\Mail\TransportInterfaceFactory;
use Magento\MediaStorage\Helper\File\Storage\Database;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;

class EmailHandlerTest extends TestCase
{
    use ObjectHelper;

    private $transportBuilder;

    public function setUp() : void
    {
        $this->markTestIncomplete();
        $this->transportBuilder = $this->prophesize(\Magento\Framework\Mail\Template\TransportBuilder::class);
    }

    public function testNoEmailIsSentIfNoMessageAboveCertainLevel()
    {
        $handler = new EmailHandler(
            $this->transportBuilder->reveal(),
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

        $this->transportBuilder->getTransport()->shouldNotHaveBeenCalled();
    }

    public function testEmailSentIfMessageAboveCertainLevel()
    {
        $template = $this->getObject(\Magento\Email\Model\Template::class);
        $template->


        $template = $this->prophesize(\Magento\Framework\Mail\TemplateInterface::class);
        $template->setVars(Argument::type('array'))->willReturn($template);
        $template->setOptions(Argument::type('array'))->willReturn($template);
        $template->getType()->willReturn(\Magento\Framework\App\TemplateTypesInterface::TYPE_HTML);

        $templateFactory = $this->prophesize(\Magento\Framework\Mail\Template\Factory::class);
        $templateFactory->get('jh_import_import_report', null)->willReturn($template->reveal());

        $addressFactory = $this->prophesize(\Magento\Framework\Mail\AddressFactory::class);
        $addressFactory->create(Argument::type('array'))->will(function ($args) {
            return new \Magento\Framework\Mail\Address($args[0]['email'], $args[0]['name']);
        });
        $addressConverter = new AddressConverter($addressFactory->reveal());

        $mimePartFactory = $this->prophesize(\Magento\Framework\Mail\MimePartInterfaceFactory::class);
        $mimePartFactory->create(Argument::type('array'))->will(function (array $args) {
            return new \Laminas\Mime\Part($args[0]['content']);
        });

        $transportBuilder = $this->getObject(TransportBuilderMock::class, [
            'senderResolver' => $this->getObject(\Magento\Email\Model\Template\SenderResolver::class),
            'addressConverter' => $addressConverter,
            'templateFactory' => $templateFactory->reveal(),
            'mimePartInterfaceFactory' => $mimePartFactory->reveal()
        ]);

        $handler = new EmailHandler(
            $transportBuilder,
            ['aydin@wearejh.com'],
            'import@wearejh.com',
            LogLevel::EMERGENCY
        );

        $report = new Report([], 'product', 'source-id');
        $handler->start($report, new \DateTime('23rd December 2019 10:00:03'));
        $handler->handleMessage(new Message(LogLevel::EMERGENCY, 'Debug Info'));
        $handler->handleItemMessage(
            new ReportItem([], 100, 'sku', 'PROD1'),
            new Message(LogLevel::EMERGENCY, 'Debug Info')
        );

        $handler->finish($report, new \DateTime, 1024);

        $message = $transportBuilder->getSentMessage();
        self::assertSame('My Subject', $message->getSubject());
    }

    public function testEmailIsOnlySendDuringFinish()
    {
        $handler = new EmailHandler(
            $this->transportBuilder->reveal(),
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

        $this->transportBuilder->create(Argument::type('array'))->shouldNotBeCalled();
    }

    public function testEmailMetaCorrect()
    {
        $handler = new EmailHandler(
            $this->transportBuilder->reveal(),
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

            /** @var \Magento\Framework\Mail\EmailMessage $mail */
            $mail = $data['message'];

            if ($mail->getTo() !== ['aydin@wearejh.com']) {
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

        $this->transportBuilder->create(Argument::that($mailVerifier))->willReturn($transport)->shouldBeCalledTimes(1);

        $handler->finish($report, new \DateTime('April 13 2017 09:21'), 1024);
    }

    public function testEmailContainsAllMessages()
    {
        $handler = new EmailHandler(
            $this->transportBuilder->reveal(),
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

        $this->transportBuilder->create(Argument::that($mailVerifier))->willReturn($transport)->shouldBeCalledTimes(1);

        $handler->finish($report, new \DateTime('April 13 2017 09:21'), 1024);
    }
}
