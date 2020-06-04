<?php

declare(strict_types=1);

namespace Jh\ImportTest\Filter;

use Jh\Import\Filter\LoggingSkipNonExistingProducts;
use Jh\Import\Filter\SkipNonExistingProducts;
use Jh\Import\Import\Record;
use Jh\Import\Report\ReportItem;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;

class LoggingSkipNonExistingProductsTest extends TestCase
{
    public function testSkippedProductIsLogged() : void
    {
        $connection = $this->prophesize(ResourceConnection::class);
        $adapter   = $this->prophesize(AdapterInterface::class);
        $select    = $this->prophesize(Select::class);

        $connection->getConnection()->willReturn($adapter->reveal());
        $adapter->select()->willReturn($select->reveal());
        $select->from('catalog_product_entity', ['sku'])->willReturn($select->reveal());
        $adapter->fetchCol(Argument::type(Select::class))->willReturn(['EXISTINGSKU', 'EXISTINGSKU2']);

        $filter = new LoggingSkipNonExistingProducts(new SkipNonExistingProducts($connection->reveal()));

        $reportItem = new ReportItem([], '20', 'sku', 'NONEXISTINGSKU');
        self::assertFalse($filter->__invoke(new Record(1, ['sku' => 'NONEXISTINGSKU']), $reportItem));
        self::assertTrue($reportItem->isSuccessful());

        $reportItem = new ReportItem([], '21', 'sku', 'EXISTINGSKU');
        self::assertTrue($filter->__invoke(new Record(1, ['sku' => 'EXISTINGSKU']), $reportItem));
        self::assertTrue($reportItem->isSuccessful());
    }
}
