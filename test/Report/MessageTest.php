<?php

namespace Fps\ImportTest\Report;

use Jh\Import\Report\Message;
use PHPUnit\Framework\TestCase;

/**
 * @author Aydin Hassan <aydin@wearejh.com>
 */
class MessageTest extends TestCase
{
    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Invalid Log Level supplied: "invalid-level"
     */
    public function testExceptionIsThrownWithInvalidLogLevel()
    {
        new Message('invalid-level', 'wut');
    }

    public function testGetters()
    {
        $message = new Message('ERROR', 'something dun wrong');

        self::assertEquals('ERROR', $message->getLogLevel());
        self::assertEquals('something dun wrong', $message->getMessage());
        self::assertInstanceOf(\DateTime::class, $message->getDateTime());
    }

    public function testToArray()
    {
        $message = new Message('ERROR', 'something dun wrong');

        self::assertEquals(['log_level' => 'ERROR', 'message' => 'something dun wrong'], $message->toArray());
    }
}
