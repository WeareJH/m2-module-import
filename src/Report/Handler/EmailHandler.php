<?php

namespace Jh\Import\Report\Handler;

use Jh\Import\LogLevel;
use Jh\Import\Report\Message;
use Jh\Import\Report\Report;
use Jh\Import\Report\ReportItem;
use Magento\Framework\Mail\Message as MailMessage;
use Magento\Framework\Mail\TransportInterfaceFactory;

/**
 * @author Aydin Hassan <aydin@hotmail.co.uk>
 */
class EmailHandler implements Handler
{
    /**
     * @var TransportInterfaceFactory
     */
    private $transportFactory;

    /**
     * @var int
     */
    private $minimumLogLevel;

    /**
     * @var array
     */
    private $recipients;

    /**
     * @var string
     */
    private $fromAddress;

    /**
     * @var \DateTime
     */
    private $startTime;

    /**
     * @var bool
     */
    private $shouldSend = false;

    /**
     * @var Message[]
     */
    private $messages = [];

    /**
     * @var Message[]
     */
    private $itemMessages = [];

    /**
     * Translates Monolog log levels to html color priorities.
     */
    private static $logLevels = [
        LogLevel::DEBUG     => '#cccccc',
        LogLevel::INFO      => '#468847',
        LogLevel::NOTICE    => '#3a87ad',
        LogLevel::WARNING   => '#c09853',
        LogLevel::ERROR     => '#f0ad4e',
        LogLevel::CRITICAL  => '#FF7708',
        LogLevel::ALERT     => '#C12A19',
        LogLevel::EMERGENCY => '#000000',
    ];

    public function __construct(
        TransportInterfaceFactory $transportFactory,
        array $recipients,
        string $fromAddress,
        string $logLevel = LogLevel::ERROR
    ) {
        $this->transportFactory = $transportFactory;
        $this->minimumLogLevel =  LogLevel::$levels[$logLevel];
        $this->recipients = $recipients;
        $this->fromAddress = $fromAddress;
    }

    public function start(Report $report, \DateTime $startTime)
    {
        $this->startTime = $startTime;
    }

    public function finish(Report $report, \DateTime $finishTime, int $memoryUsage)
    {
        if ($this->shouldSend) {
            $this->send($report, $finishTime, $memoryUsage);
        }
    }

    public function handleMessage(Message $message)
    {
        $this->messages[] = $message;

        $level = LogLevel::$levels[$message->getLogLevel()];

        if ($level >= $this->minimumLogLevel) {
            $this->shouldSend = true;
        }
    }

    public function handleItemMessage(ReportItem $item, Message $message)
    {
        $this->itemMessages[] = [$item, $message];

        $level = LogLevel::$levels[$message->getLogLevel()];

        if ($level >= $this->minimumLogLevel) {
            $this->shouldSend = true;
        }
    }

    private function send(Report $report, \DateTime $finishTime, int $memoryUsage)
    {
        $content = sprintf(
            '%s%s%s%s%s%s',
            $this->title(
                sprintf(
                    'An error occurred with a severity level of at least: "%s" so we sent the whole import log over',
                    array_search($this->minimumLogLevel, LogLevel::$levels, true)
                )
            ),
            $this->info($report, $finishTime, $memoryUsage),
            $this->title('Item Level Logs', 2),
            implode('', array_map(function ($item) {
                return $this->itemLogEntry(...$item);
            }, $this->itemMessages)),
            $this->title('Import Level Logs', 2),
            implode('', array_map(function (Message $message) {
                return $this->logEntry($message);
            }, $this->messages))
        );

        $subject = sprintf(
            'A problem occurred with import: "%s" started on: "%s" and finished on: "%s"',
            $report->getImportName(),
            $this->startTime->format('d-m-Y H:i:s'),
            $finishTime->format('d-m-Y H:i:s')
        );

        $mailMessage = (new MailMessage)
            ->setMessageType(MailMessage::TYPE_HTML)
            ->addTo($this->recipients)
            ->setFrom($this->fromAddress)
            ->setSubject($subject)
            ->setBodyHtml($content);

        $this->transportFactory->create(['message' => $mailMessage])->sendMessage();
    }

    private function title(string $title, $level = 1)
    {
        $title = htmlspecialchars($title, ENT_NOQUOTES, 'UTF-8');
        return sprintf(
            '<h%d style="background: #000000;color: #ffffff;padding: 5px;" class="monolog-output">%s</h%d>',
            $level,
            $title,
            $level
        );
    }

    private function info(Report $report, \DateTime $finishTime, int $memoryUsage)
    {
        $output  = '<h2 style="background: #23F532;color: #ffffff;padding: 5px;"';
        $output .= 'class="monolog-output">Import Information</h2>';
        $output .= '<table cellspacing="1" width="100%" class="monolog-output">';
        $output .= $this->tableRow('Import Name', $report->getImportName());
        $output .= $this->tableRow('Source ID', $report->getSourceId());
        $output .= $this->tableRow('Import Started', $this->startTime->format('d-m-Y H:i:s'));
        $output .= $this->tableRow('Import Finished', $finishTime->format('d-m-Y H:i:s'));
        $output .= $this->tableRow('Peak Memory Usage', format_bytes($memoryUsage));
        return $output.'</table>';
    }

    private function logTitle(string $logLevel)
    {
        $title = htmlspecialchars($logLevel, ENT_NOQUOTES, 'UTF-8');
        return sprintf(
            '<h3 style="background: %s;color: #ffffff;padding: 5px;" class="monolog-output">%s</h1>',
            self::$logLevels[$logLevel],
            $title
        );
    }

    private function itemLogEntry(ReportItem $reportItem, Message $message)
    {
        $output  = $this->logTitle($message->getLogLevel());
        $output .= '<table cellspacing="1" width="100%" class="monolog-output">';
        $output .= $this->tableRow('Reference Line', $reportItem->getReferenceLine());
        $output .= $this->tableRow('ID Field', $reportItem->getIdField());
        $output .= $this->tableRow('ID Value', $reportItem->getIdValue());
        $output .= $this->tableRow('Message', $message->getMessage());
        $output .= $this->tableRow('Time', $message->getDateTime()->format('d-m-Y H:i:s'));

        return $output.'</table>';
    }

    private function logEntry(Message $message)
    {
        $output  = $this->logTitle($message->getLogLevel());
        $output .= '<table cellspacing="1" width="100%" class="monolog-output">';
        $output .= $this->tableRow('Message', $message->getMessage());
        $output .= $this->tableRow('Time', $message->getDateTime()->format('d-m-Y H:i:s'));

        return $output.'</table>';
    }

    private function tableRow($th, $td = ' ', $escapeTd = true)
    {
        $th = htmlspecialchars($th, ENT_NOQUOTES, 'UTF-8');
        if ($escapeTd) {
            $td = '<pre>'.htmlspecialchars($td, ENT_NOQUOTES, 'UTF-8').'</pre>';
        }

        $format  = "<tr style=\"padding: 4px;spacing: 0;text-align: left;\">\n<th style=\"background: #cccccc\"";
        $format .= "width=\"150px\">%s:</th>\n<td style=\"padding: 4px;spacing: 0;text-align:";
        $format .= "left;background: #eeeeee\">%s</td>\n</tr>";

        return sprintf($format, $th, $td);
    }
}
