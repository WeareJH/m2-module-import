<?php

namespace Jh\ImportTest\Report;

use DateTime;
use InvalidArgumentException;
use Jh\Import\Report\Message;
use PHPUnit\Framework\TestCase;

/**
 * @author Aydin Hassan <aydin@wearejh.com>
 */
class MessageTest extends TestCase
{
    public function testExceptionIsThrownWithInvalidLogLevel(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid Log Level supplied: "invalid-level"');

        new Message('invalid-level', 'wut');
    }

    public function testGetters(): void
    {
        $message = new Message('ERROR', 'something dun wrong');

        self::assertEquals('ERROR', $message->getLogLevel());
        self::assertEquals('something dun wrong', $message->getMessage());
        self::assertInstanceOf(DateTime::class, $message->getDateTime());
    }

    public function testToArray(): void
    {
        $message = new Message('ERROR', 'something dun wrong');

        self::assertEquals(['log_level' => 'ERROR', 'message' => 'something dun wrong'], $message->toArray());
    }
}
