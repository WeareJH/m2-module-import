<?php

namespace Jh\ImportTest;

use Jh\Import\LogLevel;
use PHPUnit\Framework\TestCase;

/**
 * @author Aydin Hassan <aydin@hotmail.co.uk>
 */
class LogLevelTest extends TestCase
{
    public function testLevels()
    {
        self::assertEquals(
            [
                'DEBUG' => 1,
                'INFO' => 2,
                'NOTICE' => 3,
                'WARNING' => 4,
                'ERROR' => 5,
                'CRITICAL' => 6,
                'ALERT' => 7,
                'EMERGENCY' => 8,
            ],
            LogLevel::$levels
        );
    }
}
