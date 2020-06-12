<?php

declare(strict_types=1);

namespace Jh\Import\Report\Handler\Email\Strategy;

use DateTime;
use Jh\Import\LogLevel;
use Jh\Import\Report\Message;
use Jh\Import\Report\Report;

class AboveLevelWithContext implements EmailHandlerStrategy
{
    /**
     * @var int
     */
    private $maxMessages;

    /**
     * @var int
     */
    private $minimumLogLevel;

    public function __construct(string $logLevel = LogLevel::ERROR, int $maxMessages = 100)
    {
        $this->maxMessages = $maxMessages;
        $this->minimumLogLevel =  LogLevel::$levels[$logLevel];
    }

    /**
     * @param array[ReportItem, Message]
     * @return array[ReportItem, Message]
     */
    public function filterItemMessages(array $messages): array
    {
        return collect($messages)
            ->filter(function (array $reportAndMessage) {
                [, $message] = $reportAndMessage;
                return LogLevel::$levels[$message->getLogLevel()] >= $this->minimumLogLevel;
            })
            ->map(function (array $reportAndMessage, int $i) use ($messages) {
                return array_slice($messages, $i - 5, 5, true)
                    + [$i => $reportAndMessage]
                    + array_slice($messages, $i + 1, 5, true);
            })
            ->take(floor($this->maxMessages / (5 + 5 + 1)))
            ->flatten(1)
            ->values()
            ->all();
    }

    /**
     * @param Message[] $messages
     * @return Message[]
     */
    public function filterImportMessages(array $messages): array
    {
        return collect($messages)
            ->filter(function (Message $message) {
                return LogLevel::$levels[$message->getLogLevel()] >= $this->minimumLogLevel;
            })
            ->map(function (Message $message, int $i) use ($messages) {
                return array_slice($messages, $i - 5, 5, true)
                    + [$i => $message]
                    + array_slice($messages, $i + 1, 5, true);
            })
            ->take(floor($this->maxMessages / (5 + 5 + 1)))
            ->flatten(1)
            ->values()
            ->all();
    }

    public function renderInfo(Report $report, DateTime $startTime, DateTime $finishTime, int $memoryUsage): string
    {
        // TODO: Implement renderInfo() method.
        return '';
    }

    /**
     * @param array[ReportItem, Message]
     * @return string
     */
    public function renderItemMessages(array $messages): string
    {
        // TODO: Implement renderItemMessages() method.
        return '';
    }

    /**
     * @param Message[]
     * @return string
     */
    public function renderImportMessages(array $messages): string
    {
        // TODO: Implement renderImportMessages() method.
        return '';
    }
}
