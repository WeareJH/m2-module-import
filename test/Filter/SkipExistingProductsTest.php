<?php

namespace Jh\ImportTest\Filter;

use Jh\Import\Filter\SkipExistingProducts;
use Jh\Import\Import\Record;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;

/**
 * @author Aydin Hassan <aydin@wearejh.com>
 */
class SkipExistingProductsTest extends TestCase
{
    use ProphecyTrait;

    public function testExistingProductsAreSkipped(): void
    {
        $connection = $this->prophesize(ResourceConnection::class);
        $adapter = $this->prophesize(AdapterInterface::class);
        $select = $this->prophesize(Select::class);

        $connection->getConnection()->willReturn($adapter->reveal());
        $adapter->select()->willReturn($select->reveal());
        $select->from('catalog_product_entity', ['sku'])->willReturn($select->reveal());
        $adapter->fetchCol(Argument::type(Select::class))->willReturn(['EXISTINGSKU', 'EXISTINGSKU2']);

        $filter = new SkipExistingProducts($connection->reveal());

        self::assertTrue($filter->__invoke(new Record(1, ['sku' => 'NONEXISTINGSKU'])));
        self::assertFalse($filter->__invoke(new Record(1, ['sku' => 'EXISTINGSKU'])));
    }
}
