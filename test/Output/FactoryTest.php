<?php

declare(strict_types=1);

namespace Jh\ImportTest\Output;

use Jh\Import\Output\Factory;
use Magento\Framework\App\State;
use phpmock\MockBuilder;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\NullOutput;

class FactoryTest extends TestCase
{
    use ProphecyTrait;

    public function testNullOutputIsCreatedWhenNotInDevModeOrNoTty(): void
    {
        $builder = new MockBuilder();
        $builder->setNamespace('Jh\Import\Output')
            ->setName('posix_isatty')
            ->setFunction(
                function () {
                    return false;
                }
            );

        $mock = $builder->build();
        $mock->enable();

        $appState = $this->prophesize(State::class);
        $appState->getMode()->willReturn(State::MODE_PRODUCTION);

        $output = new ConsoleOutput();

        $outputFactory = new Factory(
            $appState->reveal(),
            $output
        );

        self::assertInstanceOf(NullOutput::class, $outputFactory->get());

        $mock->disable();
    }

    public function testConsoleOutputIsCreatedWhenInDevMode(): void
    {
        $appState = $this->prophesize(State::class);
        $appState->getMode()->willReturn(State::MODE_DEVELOPER);

        $output = new ConsoleOutput();

        $outputFactory = new Factory(
            $appState->reveal(),
            $output
        );

        self::assertSame($output, $outputFactory->get());
    }


    public function testConsoleOutputIsCreatedWhenInTty(): void
    {
        $builder = new MockBuilder();
        $builder->setNamespace('Jh\Import\Output')
            ->setName('posix_isatty')
            ->setFunction(
                function () {
                    return true;
                }
            );

        $mock = $builder->build();
        $mock->enable();

        $appState = $this->prophesize(State::class);
        $appState->getMode()->willReturn(State::MODE_PRODUCTION);

        $output = new ConsoleOutput();

        $outputFactory = new Factory(
            $appState->reveal(),
            $output
        );

        self::assertSame($output, $outputFactory->get());

        $mock->disable();
    }
}
