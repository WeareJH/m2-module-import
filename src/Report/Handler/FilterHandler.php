<?php

namespace Jh\Import\Report\Handler;

use Jh\Import\LogLevel;
use Jh\Import\Report\Message;
use Jh\Import\Report\Report;
use Jh\Import\Report\ReportItem;

/**
 * @author Aydin Hassan <aydin@hotmail.co.uk>
 */
class FilterHandler implements Handler
{
    /**
     * @var Handler
     */
    private $wrappedHandler;

    /**
     * @var int
     */
    private $minimumLogLevel;

    public function __construct(string $logLevel, Handler $wrappedHandler)
    {
        if (!isset(LogLevel::$levels[$logLevel])) {
            throw new \InvalidArgumentException('Invalid log level');
        }

        $this->minimumLogLevel = LogLevel::$levels[$logLevel];
        $this->wrappedHandler = $wrappedHandler;
    }

    public function start(Report $report, \DateTime $startTime): void
    {
        $this->wrappedHandler->start($report, $startTime);
    }

    public function finish(Report $report, \DateTime $finishTime, int $memoryUsage): void
    {
        $this->wrappedHandler->finish($report, $finishTime, $memoryUsage);
    }

    public function handleMessage(Message $message): void
    {
        $level = LogLevel::$levels[$message->getLogLevel()];
        if ($level >= $this->minimumLogLevel) {
            $this->wrappedHandler->handleMessage($message);
        }
    }

    public function handleItemMessage(ReportItem $item, Message $message): void
    {
        $level = LogLevel::$levels[$message->getLogLevel()];
        if ($level >= $this->minimumLogLevel) {
            $this->wrappedHandler->handleItemMessage($item, $message);
        }
    }
}
