<?php

namespace Jh\Import\Report;

use Jh\Import\Report\Handler\Handler;
use Jh\Import\LogLevel;

/**
 * @author Aydin Hassan <aydin@wearejh.com>
 */
class ReportItem
{
    /**
     * @var Handler[]
     */
    private $handlers = [];

    /**
     * @var string
     */
    private $referenceLine;

    /**
     * @var string
     */
    private $idField;

    /**
     * @var string
     */
    private $idValue;

    /**
     * @var bool
     */
    private $isSuccessful = true;

    public function __construct(array $handlers, string $referenceLine, string $idField, string $idValue)
    {
        $this->handlers = $handlers;
        $this->referenceLine = $referenceLine;
        $this->idField = $idField;
        $this->idValue = $idValue;
    }

    public function addError(string $error)
    {
        $this->addMessage(LogLevel::ERROR, $error);
    }

    public function addWarning(string $warning)
    {
        $this->addMessage(LogLevel::WARNING, $warning);
    }

    public function addDebug(string $debug)
    {
        $this->addMessage(LogLevel::DEBUG, $debug);
    }

    public function addMessage(string $logLevel, $message)
    {
        $message = new Message($logLevel, $message);

        if (isset(Report::$failedLogLevels[$message->getLogLevel()])) {
            $this->isSuccessful = false;
        }

        foreach ($this->handlers as $handler) {
            $handler->handleItemMessage($this, $message);
        }
    }

    public function getReferenceLine(): string
    {
        return $this->referenceLine;
    }

    public function getIdField(): string
    {
        return $this->idField;
    }

    public function getIdValue(): string
    {
        return $this->idValue;
    }

    public function isSuccessful() : bool
    {
        return $this->isSuccessful;
    }
}
