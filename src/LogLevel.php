<?php

namespace Jh\Import;

/**
 * @author Aydin Hassan <aydin@hotmail.co.uk>
 */
class LogLevel
{
    public const DEBUG = 'DEBUG';
    public const INFO = 'INFO';
    public const NOTICE = 'NOTICE';
    public const WARNING = 'WARNING';
    public const ERROR = 'ERROR';
    public const CRITICAL = 'CRITICAL';
    public const ALERT = 'ALERT';
    public const EMERGENCY = 'EMERGENCY';

    /**
     * @var array $levels Logging levels
     */
    public static $levels = [
        self::DEBUG => 1,
        self::INFO => 2,
        self::NOTICE => 3,
        self::WARNING => 4,
        self::ERROR => 5,
        self::CRITICAL => 6,
        self::ALERT => 7,
        self::EMERGENCY => 8
    ];
}
