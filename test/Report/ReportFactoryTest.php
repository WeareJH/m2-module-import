<?php

namespace Jh\ImportTest\Report;

use Jh\Import\Progress\CliProgress;
use Jh\Import\Report\Handler\ConsoleHandler;
use Jh\Import\Report\Handler\DatabaseHandler;
use Jh\Import\Report\Report;
use Jh\Import\Report\ReportFactory;
use Jh\Import\Source\Iterator;
use Magento\Framework\App\State;
use phpmock\MockBuilder;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Output\NullOutput;

/**
 * @author Aydin Hassan <aydin@wearejh.com>
 */
class ReportFactoryTest extends TestCase
{

    /**
     * @runInSeparateProcess
     */
    public function testReportIsCreatedWithoutConsoleHandlerWhenNotInDevModeOrNoTty()
    {
        $builder = new MockBuilder();
        $builder->setNamespace('Jh\Import\Report')
            ->setName('posix_isatty')
            ->setFunction(
                function () {
                    return false;
                }
            );

        $mock = $builder->build();
        $mock->enable();

        $dbHandler = $this->prophesize(DatabaseHandler::class);
        $appState  = $this->prophesize(State::class);
        $appState->getMode()->willReturn(State::MODE_PRODUCTION);

        $factory = new ReportFactory(
            $appState->reveal(),
            $dbHandler->reveal(),
            new CliProgress(new NullOutput)
        );

        $report = $factory->createFromSourceAndName(new Iterator(new \ArrayIterator([])), 'product');
        self::assertInstanceOf(Report::class, $report);
        self::assertCount(1, self::readAttribute($report, 'handlers'));

        $mock->disable();
    }

    public function testReportIsCreatedWithConsoleHandlerIfInDevMode()
    {
        $appState  = $this->prophesize(State::class);
        $dbHandler = $this->prophesize(DatabaseHandler::class);

        $appState->getMode()->willReturn(State::MODE_DEVELOPER);

        $factory = new ReportFactory(
            $appState->reveal(),
            $dbHandler->reveal(),
            new CliProgress(new NullOutput)
        );

        $report = $factory->createFromSourceAndName(new Iterator(new \ArrayIterator([])), 'product');

        $handlers = self::readAttribute($report, 'handlers');

        self::assertInstanceOf(Report::class, $report);
        self::assertCount(2, $handlers);
        self::assertInstanceOf(ConsoleHandler::class, $handlers[1]);
    }

    /**
     * @runInSeparateProcess
     */
    public function testReportIsCreatedWithConsoleHandlerWhenTty()
    {
        $builder = new MockBuilder();
        $builder->setNamespace('Jh\Import\Report')
            ->setName('posix_isatty')
            ->setFunction(
                function () {
                    return true;
                }
            );

        $mock = $builder->build();
        $mock->enable();

        $dbHandler = $this->prophesize(DatabaseHandler::class);
        $appState  = $this->prophesize(State::class);
        $appState->getMode()->willReturn(State::MODE_PRODUCTION);

        $factory = new ReportFactory(
            $appState->reveal(),
            $dbHandler->reveal(),
            new CliProgress(new NullOutput)
        );

        $report = $factory->createFromSourceAndName(new Iterator(new \ArrayIterator([])), 'product');

        $handlers = self::readAttribute($report, 'handlers');

        self::assertInstanceOf(Report::class, $report);
        self::assertCount(2, $handlers);
        self::assertInstanceOf(ConsoleHandler::class, $handlers[1]);

        $mock->disable();
    }
}
