<?php

namespace Jh\Import\Report\Handler;

use Jh\Import\LogLevel;
use Jh\Import\Report\Handler\Email\Strategy\EmailHandlerStrategy;
use Jh\Import\Report\Handler\Email\Renderer;
use Jh\Import\Report\Message;
use Jh\Import\Report\Report;
use Jh\Import\Report\ReportItem;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\App\Area;
use Magento\Store\Model\Store;

/**
 * @author Aydin Hassan <aydin@hotmail.co.uk>
 */
class EmailHandler implements Handler
{
    /**
     * @var TransportBuilder
     */
    private $transportBuilder;

    /**
     * @var EmailHandlerStrategy
     */
    private $strategy;

    /**
     * @var array
     */
    private $recipients;

    /**
     * @var string
     */
    private $fromAddress;

    /**
     * @var string
     */
    private $fromName;

    /**
     * @var \DateTime
     */
    private $startTime;

    /**
     * @var Message[]
     */
    private $messages = [];

    /**
     * @var Message[]
     */
    private $itemMessages = [];

    public function __construct(
        TransportBuilder $transportBuilder,
        EmailHandlerStrategy $emailHandlerStrategy,
        array $recipients,
        string $fromAddress,
        string $fromName
    ) {
        $this->transportBuilder = $transportBuilder;
        $this->strategy = $emailHandlerStrategy;
        $this->recipients = $recipients;
        $this->fromAddress = $fromAddress;
        $this->fromName = $fromName;
    }

    public function start(Report $report, \DateTime $startTime): void
    {
        $this->startTime = $startTime;
    }

    public function finish(Report $report, \DateTime $finishTime, int $memoryUsage): void
    {
        $this->send($report, $finishTime, $memoryUsage);
    }

    public function handleMessage(Message $message): void
    {
        $this->messages[] = $message;
    }

    public function handleItemMessage(ReportItem $item, Message $message): void
    {
        $this->itemMessages[] = [$item, $message];
    }

    private function send(Report $report, \DateTime $finishTime, int $memoryUsage): void
    {
        $itemMessages = $this->strategy->filterItemMessages($this->itemMessages);
        $importMessages = $this->strategy->filterImportMessages($this->messages);

        if (empty($itemMessages) && empty($importMessages)) {
            return;
        }

        $content = sprintf(
            '%s%s%s%s%s',
            $this->strategy->renderInfo($report, $this->startTime, $finishTime, $memoryUsage),
            Renderer::title('Item Level Logs', 2),
            $this->strategy->renderItemMessages($itemMessages),
            Renderer::title('Import Level Logs', 2),
            $this->strategy->renderImportMessages($importMessages)
        );

        $subject = sprintf(
            'A problem occurred with import: "%s" started on: "%s" and finished on: "%s"',
            $report->getImportName(),
            $this->startTime->format('d-m-Y H:i:s'),
            $finishTime->format('d-m-Y H:i:s')
        );

        $this->transportBuilder
            ->setTemplateIdentifier('jh_import_import_report')
            ->setTemplateOptions(['area' => Area::AREA_FRONTEND, 'store' => Store::DEFAULT_STORE_ID])
            ->setTemplateVars(['subject' => $subject, 'content' => $content])
            ->setFromByScope(['email' => $this->fromAddress, 'name' => $this->fromName]);

        foreach ($this->recipients as $name => $email) {
            $this->transportBuilder
                ->addTo($email, $name);
        }

        $this->transportBuilder
            ->getTransport()
            ->sendMessage();
    }
}
