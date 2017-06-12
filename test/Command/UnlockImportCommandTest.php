<?php

namespace Jh\ImportTest\Command;

use Jh\Import\Command\RunImportCommand;
use Jh\Import\Command\UnlockImportCommand;
use Jh\Import\Config\Data;
use Jh\Import\Import\Manager;
use Jh\Import\Locker\Locker;
use Magento\Framework\App\State;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @author Aydin Hassan <aydin@wearejh.com>
 */
class UnlockImportCommandTest extends TestCase
{
    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Cannot find configuration for import with name: "product"
     */
    public function testExceptionIsThrownIfImportDoesNotExist()
    {
        $config  = $this->prophesize(Data::class);
        $locker  = $this->prophesize(Locker::class);

        $config->hasImport('product')->willReturn(false);

        $commandTester = new CommandTester(new UnlockImportCommand($config->reveal(), $locker->reveal()));
        $commandTester->execute(['import_name' => 'product']);
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Import: "product" is not locked
     */
    public function testExceptionIsThrownIsImportNotLocked()
    {
        $config  = $this->prophesize(Data::class);
        $locker  = $this->prophesize(Locker::class);

        $config->hasImport('product')->willReturn(true);
        $locker->locked('product')->willReturn(false);

        $commandTester = new CommandTester(new UnlockImportCommand($config->reveal(), $locker->reveal()));
        $commandTester->execute(['import_name' => 'product']);
    }

    public function testSuccessfullyReleaseLock()
    {
        $config  = $this->prophesize(Data::class);
        $locker  = $this->prophesize(Locker::class);

        $config->hasImport('product')->willReturn(true);
        $locker->locked('product')->willReturn(true);
        $locker->release('product')->shouldBeCalled();

        $commandTester = new CommandTester(new UnlockImportCommand($config->reveal(), $locker->reveal()));
        $commandTester->execute(['import_name' => 'product']);

        self::assertContains('The lock for import: "product" has been released', $commandTester->getDisplay());
    }
}
