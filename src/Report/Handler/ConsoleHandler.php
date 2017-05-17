<?php

namespace Jh\Import\Report\Handler;

use Jh\Import\LogLevel;
use Jh\Import\Progress\CliProgress;
use Jh\Import\Report\Message;
use Jh\Import\Report\Report;
use Jh\Import\Report\ReportItem;

/**
 * @author Aydin Hassan <aydin@hotmail.co.uk>
 */
class ConsoleHandler implements Handler
{
    /**
     * @var array
     */
    private $acceptedLogLevels;

    /**
     * @var CliProgress
     */
    private $cliProgress;

    public function __construct(CliProgress $cliProgress, $minErrorLevel)
    {
        if (!isset(LogLevel::$levels[$minErrorLevel])) {
            throw new \InvalidArgumentException('Non existent log level');
        }

        $this->acceptedLogLevels = array_filter(LogLevel::$levels, function ($level) use ($minErrorLevel) {
            return $level >= LogLevel::$levels[$minErrorLevel];
        });
        $this->cliProgress = $cliProgress;
    }


    public function start(Report $report, \DateTime $startTime)
    {
        // noop
    }

    public function finish(Report $report, \DateTime $finishTime, int $memoryUsage)
    {
        // noop
    }

    public function handleMessage(Message $message)
    {
        $this->write($message);
    }

    public function handleItemMessage(ReportItem $item, Message $message)
    {
        $this->write($message);
    }

    private function write(Message $message)
    {
        if (!isset($this->acceptedLogLevels[$message->getLogLevel()])) {
            return;
        }

        $this->cliProgress->addLog($message->getLogLevel(), $message->getMessage());
    }
}
