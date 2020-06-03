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
    public function testExceptionIsThrownIfImportDoesNotExist()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot find configuration for import with name: "product"');

        $config  = $this->prophesize(Data::class);
        $locker  = $this->prophesize(Locker::class);

        $config->hasImport('product')->willReturn(false);

        $commandTester = new CommandTester(new UnlockImportCommand($config->reveal(), $locker->reveal()));
        $commandTester->execute(['import_name' => 'product']);
    }

    public function testExceptionIsThrownIsImportNotLocked()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Import: "product" is not locked');

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

        self::assertStringContainsString(
            'The lock for import: "product" has been released',
            $commandTester->getDisplay()
        );
    }
}
