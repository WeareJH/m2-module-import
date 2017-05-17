<?php

namespace Jh\Import;

/**
 * @author Aydin Hassan <aydin@hotmail.co.uk>
 */
class LogLevel
{
    const DEBUG = 'DEBUG';
    const INFO = 'INFO';
    const NOTICE = 'NOTICE';
    const WARNING = 'WARNING';
    const ERROR = 'ERROR';
    const CRITICAL = 'CRITICAL';
    const ALERT = 'ALERT';
    const EMERGENCY = 'EMERGENCY';

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
