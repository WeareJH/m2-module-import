<?php

declare(strict_types=1);

namespace Jh\Import\Report\Handler\Email\Strategy;

use DateTime;
use Jh\Import\LogLevel;
use Jh\Import\Report\Handler\Email\Renderer;
use Jh\Import\Report\Message;
use Jh\Import\Report\Report;
use Jh\Import\Report\ReportItem;

class FingersCrossedMax implements EmailHandlerStrategy
{
    /**
     * @var int
     */
    private $minimumLogLevel;

    /**
     * @var int
     */
    private $maxMessages;

    public function __construct(string $logLevel = LogLevel::ERROR, int $maxMessages = 100)
    {
        $this->minimumLogLevel = LogLevel::$levels[$logLevel];
        $this->maxMessages = $maxMessages;
    }

    /**
     * @param array[ReportItem, Message]
     * @return array[ReportItem, Message]
     */
    public function filterItemMessages(array $messages): array
    {
        return collect($messages)
            ->skipUntil(function (array $reportAndMessage) {
                [, $message] = $reportAndMessage;
                return LogLevel::$levels[$message->getLogLevel()] >= $this->minimumLogLevel;
            })
            ->take($this->maxMessages)
            ->all();
    }

    /**
     * @param Message[] $messages
     * @return Message[]
     */
    public function filterImportMessages(array $messages): array
    {
        return collect($messages)
            ->skipUntil(function (Message $message) {
                return LogLevel::$levels[$message->getLogLevel()] >= $this->minimumLogLevel;
            })
            ->take($this->maxMessages)
            ->all();
    }

    public function renderInfo(Report $report, DateTime $startTime, DateTime $finishTime, int $memoryUsage): string
    {
        $output = Renderer::title(sprintf(
            'An error occurred with a severity level of at least: "%s" so we sent all messages after that (maximum %d)',
            array_search($this->minimumLogLevel, LogLevel::$levels, true),
            $this->maxMessages
        ));

        $output .= '<h2 style="background: #23F532;color: #ffffff;padding: 5px;"';
        $output .= 'class="monolog-output">Import Information</h2>';
        $output .= '<table cellspacing="1" width="100%" class="monolog-output">';
        $output .= Renderer::tableRow('Import Name', $report->getImportName());
        $output .= Renderer::tableRow('Source ID', $report->getSourceId());
        $output .= Renderer::tableRow('Import Started', $startTime->format('d-m-Y H:i:s'));
        $output .= Renderer::tableRow('Import Finished', $finishTime->format('d-m-Y H:i:s'));
        $output .= Renderer::tableRow('Peak Memory Usage', format_bytes($memoryUsage));
        return $output.'</table>';
    }

    /**
     * @param array[ReportItem, Message]
     * @return string
     */
    public function renderItemMessages(array $messages): string
    {
        return implode('', array_map(function ($item) {
            return Renderer::itemLogEntry(...$item);
        }, $messages));
    }

    /**
     * @param Message[]
     * @return string
     */
    public function renderImportMessages(array $messages): string
    {
        return implode('', array_map(function ($message) {
            return Renderer::importLogEntry($message);
        }, $messages));
    }
}
