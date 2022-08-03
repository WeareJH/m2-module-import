<?php

namespace Jh\ImportTest\Report\Handler;

use Jh\Import\LogLevel;
use Jh\Import\Report\Handler\CollectingHandler;
use Jh\Import\Report\Message;
use Jh\Import\Report\ReportItem;
use PHPUnit\Framework\TestCase;

/**
 * @author Aydin Hassan <aydin@hotmail.co.uk>
 */
class CollectingHandlerTest extends TestCase
{
    public function testMessagesAreCollected(): void
    {
        $handler = new CollectingHandler();
        $handler->handleMessage(new Message(LogLevel::CRITICAL, 'Message 1'));
        $handler->handleMessage(new Message(LogLevel::EMERGENCY, 'Message 2'));
        $handler->handleItemMessage(
            new ReportItem([], 103, 'sku', 'PROD1'),
            new Message(LogLevel::EMERGENCY, 'Item Message 1')
        );

        self::assertEquals(
            [
                [
                    'log_level' => 'CRITICAL',
                    'message'   => 'Message 1'
                ],
                [
                    'log_level' => 'EMERGENCY',
                    'message'   => 'Message 2'
                ]
            ],
            $handler->messages
        );

        self::assertEquals(
            [
                [
                    'log_level' => 'EMERGENCY',
                    'message'   => 'Item Message 1'
                ],
            ],
            $handler->itemMessages
        );
    }
}
