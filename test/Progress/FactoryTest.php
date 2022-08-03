<?php

declare(strict_types=1);

namespace Jh\ImportTest\Progress;

use Jh\Import\Progress\CliProgress;
use Jh\Import\Progress\Factory;
use Jh\Import\Progress\NullProgress;
use Magento\Framework\App\State;
use phpmock\MockBuilder;
use Prophecy\PhpUnit\ProphecyTrait;
use PHPUnit\Framework\TestCase;

class FactoryTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @runInSeparateProcess
     */
    public function testNullProgressIsCreatedWhenNotInDevModeOrNoTty(): void
    {
        $builder = new MockBuilder();
        $builder->setNamespace('Jh\Import\Progress')
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

        $cliProgress = $this->prophesize(CliProgress::class);

        $progressFactory = new Factory(
            $appState->reveal(),
            $cliProgress->reveal()
        );

        $progress = $progressFactory->get();

        self::assertInstanceOf(NullProgress::class, $progress);
    }

    public function testCliProgressIsCreatedWhenInDevMode(): void
    {
        $appState = $this->prophesize(State::class);
        $appState->getMode()->willReturn(State::MODE_DEVELOPER);

        $cliProgress = $this->prophesize(CliProgress::class);

        $progressFactory = new Factory(
            $appState->reveal(),
            $cliProgress->reveal()
        );

        $progress = $progressFactory->get();

        self::assertInstanceOf(CliProgress::class, $progress);
    }

    /**
     * @runInSeparateProcess
     */
    public function testCliProgressIsCreatedWhenInTty(): void
    {
        $builder = new MockBuilder();
        $builder->setNamespace('Jh\Import\Progress')
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

        $cliProgress = $this->prophesize(CliProgress::class);

        $progressFactory = new Factory(
            $appState->reveal(),
            $cliProgress->reveal()
        );

        $progress = $progressFactory->get();

        self::assertInstanceOf(CliProgress::class, $progress);
    }
}
