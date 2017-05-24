<?php

namespace Jh\ImportTest\Report;

use Jh\Import\Config;
use Jh\Import\LogLevel;
use Jh\Import\Progress\CliProgress;
use Jh\Import\Report\Handler\ConsoleHandler;
use Jh\Import\Report\Handler\DatabaseHandler;
use Jh\Import\Report\Report;
use Jh\Import\Report\ReportFactory;
use Jh\Import\Source\Iterator;
use Magento\Framework\App\State;
use Magento\Framework\ObjectManagerInterface;
use phpmock\MockBuilder;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
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

        $appState  = $this->prophesize(State::class);
        $appState->getMode()->willReturn(State::MODE_PRODUCTION);

        $services = [
            State::class => $appState,
            DatabaseHandler::class => $this->prophesize(DatabaseHandler::class)->reveal()
        ];

        $objectManager = $this->prophesize(ObjectManagerInterface::class);
        $objectManager->get(Argument::type('string'))->will(function ($args) use ($services) {
            return $services[$args[0]];
        });

        $factory = new ReportFactory($objectManager->reveal());

        $report = $factory->createFromSourceAndConfig(new Iterator(new \ArrayIterator([])), new Config('product', []));
        self::assertInstanceOf(Report::class, $report);
        self::assertCount(1, self::readAttribute($report, 'handlers'));

        $mock->disable();
    }

    public function testReportIsCreatedWithConsoleHandlerIfInDevMode()
    {
        $appState  = $this->prophesize(State::class);
        $appState->getMode()->willReturn(State::MODE_DEVELOPER);

        $services = [
            State::class => $appState,
            DatabaseHandler::class => $this->prophesize(DatabaseHandler::class)->reveal(),
        ];

        $objectManager = $this->prophesize(ObjectManagerInterface::class);
        $objectManager->get(Argument::type('string'))->will(function ($args) use ($services) {
            return $services[$args[0]];
        });

        $objectManager
            ->create(ConsoleHandler::class, ['minErrorLevel' => LogLevel::WARNING])
            ->willReturn(new ConsoleHandler(new CliProgress(new NullOutput), LogLevel::WARNING));

        $factory = new ReportFactory($objectManager->reveal());

        $report = $factory->createFromSourceAndConfig(new Iterator(new \ArrayIterator([])), new Config('product', []));
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

        $appState  = $this->prophesize(State::class);
        $appState->getMode()->willReturn(State::MODE_PRODUCTION);

        $services = [
            State::class => $appState,
            DatabaseHandler::class => $this->prophesize(DatabaseHandler::class)->reveal(),
        ];

        $objectManager = $this->prophesize(ObjectManagerInterface::class);
        $objectManager->get(Argument::type('string'))->will(function ($args) use ($services) {
            return $services[$args[0]];
        });

        $objectManager
            ->create(ConsoleHandler::class, ['minErrorLevel' => LogLevel::WARNING])
            ->willReturn(new ConsoleHandler(new CliProgress(new NullOutput), LogLevel::WARNING));

        $factory = new ReportFactory($objectManager->reveal());

        $report = $factory->createFromSourceAndConfig(new Iterator(new \ArrayIterator([])), new Config('product', []));
        $handlers = self::readAttribute($report, 'handlers');

        self::assertInstanceOf(Report::class, $report);
        self::assertCount(2, $handlers);
        self::assertInstanceOf(ConsoleHandler::class, $handlers[1]);

        $mock->disable();
    }
}
