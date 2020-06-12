<?php

namespace Jh\ImportTest\Import;

use Jh\Import\Config;
use Jh\Import\Filter\SkipNonExistingProducts;
use Jh\Import\Import\Record;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;

/**
 * @author Aydin Hassan <aydin@wearejh.com>
 */
class SkipNonExistingProductsTest extends TestCase
{
    public function testNonExistingProductsAreSkipped(): void
    {
        $connection = $this->prophesize(ResourceConnection::class);
        $adapter   = $this->prophesize(AdapterInterface::class);
        $select    = $this->prophesize(Select::class);

        $connection->getConnection()->willReturn($adapter->reveal());
        $adapter->select()->willReturn($select->reveal());
        $select->from('catalog_product_entity', ['sku'])->willReturn($select->reveal());
        $adapter->fetchCol(Argument::type(Select::class))->willReturn(['EXISTINGSKU', 'EXISTINGSKU2']);

        $filter = new SkipNonExistingProducts($connection->reveal());

        self::assertFalse($filter->__invoke(new Record(1, ['sku' => 'NONEXISTINGSKU'])));
        self::assertTrue($filter->__invoke(new Record(1, ['sku' => 'EXISTINGSKU'])));
    }

    public function testNonExistingProductsAreSkippedWithCustomSkuField(): void
    {
        $connection = $this->prophesize(ResourceConnection::class);
        $adapter   = $this->prophesize(AdapterInterface::class);
        $select    = $this->prophesize(Select::class);

        $connection->getConnection()->willReturn($adapter->reveal());
        $adapter->select()->willReturn($select->reveal());
        $select->from('catalog_product_entity', ['sku'])->willReturn($select->reveal());
        $adapter->fetchCol(Argument::type(Select::class))->willReturn(['EXISTINGSKU', 'EXISTINGSKU2']);

        $filter = new SkipNonExistingProducts($connection->reveal());
        $filter->prepare(new Config('my-import', ['id_field' => 'my-sku-field']));

        self::assertFalse($filter->__invoke(new Record(1, ['my-sku-field' => 'NONEXISTINGSKU'])));
        self::assertTrue($filter->__invoke(new Record(1, ['my-sku-field' => 'EXISTINGSKU'])));
    }
}
