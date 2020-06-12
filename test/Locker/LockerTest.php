<?php

namespace Jh\ImportTest\Locker;

use Jh\Import\Locker\Locker;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use PHPUnit\Framework\TestCase;

class LockerTest extends TestCase
{
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

    public function testLockThrowsExceptionIfAlreadyLocked()
    {
        $this->expectException(\Jh\Import\Locker\ImportLockedException::class);
        $this->expectExceptionMessage('Import with name "product" is locked.');
        
        $select = $this->prophesize(\Magento\Framework\DB\Select::class);

        $this->dbAdapter->select()->willReturn($select);

        $select->from('jh_import_lock')->willReturn($select);
        $select->where('import_name = ?', 'product')->willReturn($select);

        $this->dbAdapter->fetchAll($select)->willReturn([['product']]);

        $this->locker->lock('product');
    }

    public function testLockInsertsDatabaseEntry()
    {
        $select = $this->prophesize(\Magento\Framework\DB\Select::class);

        $this->dbAdapter->select()->willReturn($select);

        $select->from('jh_import_lock')->willReturn($select);
        $select->where('import_name = ?', 'product')->willReturn($select);

        $this->dbAdapter->fetchAll($select)->willReturn([]);

        $this->dbAdapter->insert('jh_import_lock', ['import_name' => 'product'])->shouldBeCalled();

        $this->locker->lock('product');
    }

    public function testReleaseDeletesDatabaseEntry()
    {
        $this->dbAdapter->delete('jh_import_lock', ['import_name = ?' => 'product'])->shouldBeCalled();

        $this->locker->release('product');
    }

    public function testLockedReturnsTrueIfLocked()
    {
        $select = $this->prophesize(\Magento\Framework\DB\Select::class);

        $this->dbAdapter->select()->willReturn($select);

        $select->from('jh_import_lock')->willReturn($select);
        $select->where('import_name = ?', 'product')->willReturn($select);

        $this->dbAdapter->fetchAll($select)->willReturn([['product']]);

        self::assertTrue($this->locker->locked('product'));
    }

    public function testLockedReturnsFalseIfLocked()
    {
        $select = $this->prophesize(\Magento\Framework\DB\Select::class);

        $this->dbAdapter->select()->willReturn($select);

        $select->from('jh_import_lock')->willReturn($select);
        $select->where('import_name = ?', 'product')->willReturn($select);

        $this->dbAdapter->fetchAll($select)->willReturn([]);

        self::assertFalse($this->locker->locked('product'));
    }
}
