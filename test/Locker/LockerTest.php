<?php

namespace Jh\ImportTest\Locker;

use Jh\Import\Locker\ImportLockedException;
use Jh\Import\Locker\Locker;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;

class LockerTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var AdapterInterface
     */
    private $dbAdapter;

    /**
     * @var Locker
     */
    private $locker;

    public function setUp(): void
    {
        $this->dbAdapter = $this->prophesize(AdapterInterface::class);
        $resourceConnection =  $this->prophesize(ResourceConnection::class);
        $resourceConnection->getConnection()->willReturn($this->dbAdapter->reveal());
        $this->locker = new Locker($resourceConnection->reveal());
    }

    public function testLockThrowsExceptionIfAlreadyLocked(): void
    {
        $this->expectException(ImportLockedException::class);
        $this->expectExceptionMessage('Import with name "product" is locked.');

        $select = $this->prophesize(Select::class);

        $this->dbAdapter->select()->willReturn($select);

        $select->from('jh_import_lock')->willReturn($select);
        $select->where('import_name = ?', 'product')->willReturn($select);

        $this->dbAdapter->fetchAll($select)->willReturn([['product']]);

        $this->locker->lock('product');
    }

    public function testLockInsertsDatabaseEntry(): void
    {
        $select = $this->prophesize(Select::class);

        $this->dbAdapter->select()->willReturn($select);

        $select->from('jh_import_lock')->willReturn($select);
        $select->where('import_name = ?', 'product')->willReturn($select);

        $this->dbAdapter->fetchAll($select)->willReturn([]);

        $this->dbAdapter->insert('jh_import_lock', ['import_name' => 'product'])->shouldBeCalled();

        $this->locker->lock('product');
    }

    public function testReleaseDeletesDatabaseEntry(): void
    {
        $this->dbAdapter->delete('jh_import_lock', ['import_name = ?' => 'product'])->shouldBeCalled();

        $this->locker->release('product');
    }

    public function testLockedReturnsTrueIfLocked(): void
    {
        $select = $this->prophesize(Select::class);

        $this->dbAdapter->select()->willReturn($select);

        $select->from('jh_import_lock')->willReturn($select);
        $select->where('import_name = ?', 'product')->willReturn($select);

        $this->dbAdapter->fetchAll($select)->willReturn([['product']]);

        self::assertTrue($this->locker->locked('product'));
    }

    public function testLockedReturnsFalseIfLocked(): void
    {
        $select = $this->prophesize(Select::class);

        $this->dbAdapter->select()->willReturn($select);

        $select->from('jh_import_lock')->willReturn($select);
        $select->where('import_name = ?', 'product')->willReturn($select);

        $this->dbAdapter->fetchAll($select)->willReturn([]);

        self::assertFalse($this->locker->locked('product'));
    }
}
