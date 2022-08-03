<?php

namespace Jh\ImportTest\Command;

use Jh\Import\Command\RunImportCommand;
use Jh\Import\Import\Manager;
use Magento\Framework\App\State;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @author Aydin Hassan <aydin@wearejh.com>
 */
class RunImportCommandTest extends TestCase
{
    use ProphecyTrait;

    public function testCronExecutesCorrectImport(): void
    {
        $manager = $this->prophesize(Manager::class);
        $state = $this->prophesize(State::class);

        $commandTester = new CommandTester(new RunImportCommand($manager->reveal(), $state->reveal()));
        $commandTester->execute(['import_name' => 'product']);

        $manager->executeImportByName('product')->shouldHaveBeenCalled();
    }

    public function testCronSetsStateToCronAndExecutesCorrectImport(): void
    {
        $manager = $this->prophesize(Manager::class);
        $state = $this->prophesize(State::class);

        $commandTester = new CommandTester(new RunImportCommand($manager->reveal(), $state->reveal()));
        $commandTester->execute(['import_name' => 'product']);

        $state->setAreaCode('crontab')->shouldHaveBeenCalled();
        $manager->executeImportByName('product')->shouldHaveBeenCalled();
    }
}
