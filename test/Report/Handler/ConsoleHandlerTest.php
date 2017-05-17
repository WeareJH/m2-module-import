<?php

namespace Jh\ImportTest\Report\Handler;

use Jh\Import\LogLevel;
use Jh\Import\Progress\CliProgress;
use Jh\Import\Report\Handler\ConsoleHandler;
use Jh\Import\Report\Message;
use PHPUnit\Framework\TestCase;

/**
 * @author Aydin Hassan <aydin@hotmail.co.uk>
 */
class ConsoleHandlerTest extends TestCase
{
    public function testMessageBelowMinErrorLevelIsNotWritten()
    {
        $cliProgress = $this->prophesize(CliProgress::class);
        $handler = new ConsoleHandler($cliProgress->reveal(), LogLevel::ERROR);

        $handler->handleMessage(new Message(LogLevel::WARNING, 'Do not show me'));

        $cliProgress->addLog()->shouldNotHaveBeenCalled();
    }

    public function testMessageAtMinErrorLevelIsWritten()
    {
        $cliProgress = $this->prophesize(CliProgress::class);
        $handler = new ConsoleHandler($cliProgress->reveal(), LogLevel::ERROR);

        $handler->handleMessage(
            new Message(LogLevel::ERROR, 'Do show me', new \DateTime('22 March 2017 13:50:00'))
        );

        $cliProgress->addLog('ERROR', 'Do show me')->shouldHaveBeenCalled();
    }

    public function testMessageAboveMinErrorLevelIsWritten()
    {
        $cliProgress = $this->prophesize(CliProgress::class);
        $handler = new ConsoleHandler($cliProgress->reveal(), LogLevel::ERROR);

        $handler->handleMessage(
            new Message(LogLevel::CRITICAL, 'Definitely show me', new \DateTime('22 March 2017 13:50:00'))
        );

        $cliProgress->addLog('CRITICAL', 'Definitely show me')->shouldHaveBeenCalled();
    }
}
