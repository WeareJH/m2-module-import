<?php

namespace Jh\Import\Report;

use Jh\Import\LogLevel;

/**
 * @author Aydin Hassan <aydin@wearejh.com>
 */
class Message
{
    /**
     * @var string
     */
    private $logLevel;

    /**
     * @var string
     */
    private $message;

    /**
     * @var \DateTime
     */
    private $dateTime;

    public function __construct(string $logLevel, string $message, \DateTime $dateTime = null)
    {
        $this->logLevel = $logLevel;
        $this->message = $message;
        $this->dateTime = $dateTime ?: new \DateTime;

        if (!isset(LogLevel::$levels[$this->logLevel])) {
            throw new \InvalidArgumentException(sprintf('Invalid Log Level supplied: "%s"', $logLevel));
        }
    }

    public function getLogLevel() : string
    {
        return $this->logLevel;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getDateTime(): \DateTime
    {
        return $this->dateTime;
    }

    public function toArray() : array
    {
        return [
            'log_level' => $this->getLogLevel(),
            'message'   => $this->getMessage(),
        ];
    }
}
