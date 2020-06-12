<?php

namespace Jh\Import\Report;

use Jh\Import\Report\Handler\Handler;
use Jh\Import\LogLevel;

/**
 * @author Aydin Hassan <aydin@wearejh.com>
 */
class Report
{
    /**
     * @var Handler[]
     */
    private $handlers = [];

    /**
     * @var string
     */
    private $importName;

    /**
     * @var string
     */
    private $sourceId;

    /**
     * @var bool
     */
    private $isSuccessful = true;

    /**
     * @var ReportItem[]
     */
    private $items = [];

    /**
     * @var array
     */
    public static $failedLogLevels = [
        LogLevel::EMERGENCY => LogLevel::EMERGENCY,
        LogLevel::ALERT => LogLevel::ALERT,
        LogLevel::CRITICAL => LogLevel::CRITICAL,
        LogLevel::ERROR => LogLevel::ERROR,
    ];

    public function __construct(array $handlers, string $importName, string $sourceId)
    {
        $this->handlers = $handlers;
        $this->importName = $importName;
        $this->sourceId = $sourceId;
    }

    public function addHandler(Handler $handler)
    {
        $this->handlers[] = $handler;
    }

    public function start(\DateTime $startTime = null)
    {
        $startTime = $startTime ?: new \DateTime();
        foreach ($this->handlers as $handler) {
            $handler->start($this, $startTime);
        }
    }

    public function newItem(string $referenceLine, string $idField, string $idValue): ReportItem
    {
        $this->items[] = $item = new ReportItem($this->handlers, $referenceLine, $idField, $idValue);
        return $item;
    }

    /**
     * @return string
     */
    public function getImportName(): string
    {
        return $this->importName;
    }

    /**
     * @return string
     */
    public function getSourceId(): string
    {
        return $this->sourceId;
    }

    public function addError(string $error)
    {
        $this->addMessage(LogLevel::ERROR, $error);
    }

    public function addWarning(string $warning)
    {
        $this->addMessage(LogLevel::WARNING, $warning);
    }

    public function addMessage(string $logLevel, $message)
    {
        $message = new Message($logLevel, $message);

        if (isset(static::$failedLogLevels[$message->getLogLevel()])) {
            $this->isSuccessful = false;
        }

        foreach ($this->handlers as $handler) {
            $handler->handleMessage($message);
        }
    }

    public function finish(\DateTime $finishTime = null, int $memoryUsage = null)
    {
        $finishTime  = $finishTime ?: new \DateTime();
        $memoryUsage = $memoryUsage ?: memory_get_usage(true);
        foreach ($this->handlers as $handler) {
            $handler->finish($this, $finishTime, $memoryUsage);
        }
    }

    public function isSuccessful(): bool
    {
        if (!$this->isSuccessful) {
            return false;
        }

        return array_reduce($this->items, function ($carry, ReportItem $reportItem) {
            if (!$carry) {
                return false;
            }
            return $reportItem->isSuccessful();
        }, true);
    }

    public function getItems(): array
    {
        return $this->items;
    }

    public function getHandlers(): array
    {
        return $this->handlers;
    }
}
