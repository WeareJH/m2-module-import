<?php

declare(strict_types=1);

namespace Jh\ImportTest\Writer;

use Jh\Import\Config;
use Jh\Import\Import\Record;
use Jh\Import\Report\CollectingReport;
use Jh\Import\Report\Handler\CollectingHandler;
use Jh\Import\Report\ReportItem;
use Jh\Import\Source\Source;
use Jh\Import\Writer\StockWriter;
use Jh\Import\Writer\Utils\DisableEventObserver;
use Jh\UnitTestHelpers\ObjectHelper;
use Magento\CatalogInventory\Api\Data\StockItemInterface;
use Magento\CatalogInventory\Api\StockConfigurationInterface;
use Magento\CatalogInventory\Api\StockItemRepositoryInterface;
use Magento\CatalogInventory\Model\StockRegistryProvider;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;

class StockWriterTest extends TestCase
{
    use ObjectHelper;
    use ProphecyTrait;

    /**
     * @var StockWriter
     */
    private $writer;

    /**
     * @var StockRegistryProvider
     */
    private $stockRegistryProvider;

    /**
     * @var StockItemRepositoryInterface
     */
    private $stockItemRepository;

    /**
     * @var StockConfigurationInterface
     */
    private $stockConfiguration;

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var AdapterInterface
     */
    private $adapter;

    public function setUp(): void
    {
        $this->stockRegistryProvider = $this->prophesize(StockRegistryProvider::class);
        $this->stockItemRepository = $this->prophesize(StockItemRepositoryInterface::class);
        $this->stockConfiguration = $this->prophesize(StockConfigurationInterface::class);
        $this->resourceConnection = $this->prophesize(ResourceConnection::class);
        $this->adapter = $this->prophesize(AdapterInterface::class);
        $select = $this->prophesize(Select::class);

        $this->resourceConnection->getConnection()->willReturn($this->adapter->reveal());
        $this->adapter->select()->willReturn($select->reveal());
        $select->from('catalog_product_entity', ['sku', 'entity_id'])->willReturn($select->reveal());
        $this->adapter->fetchPairs(Argument::type(Select::class))
            ->willReturn(['PROD1' => 10, 'PROD2' => 11, 'PROD3' => 12]);

        $this->stockConfiguration->getDefaultScopeId()->willReturn(2);

        $this->writer = new StockWriter(
            $this->stockRegistryProvider->reveal(),
            $this->stockItemRepository->reveal(),
            $this->stockConfiguration->reveal(),
            $this->resourceConnection->reveal(),
            new DisableEventObserver()
        );
    }

    public function testErrorIsAddedIfProductDoesNotExist(): void
    {
        $record = new Record(10, [
            'sku' => 'PROD30',
            'qty' => 10,
        ]);

        $this->writer->write(
            $record,
            new ReportItem([$handler = new CollectingHandler()], '100', 'sku', '100')
        );

        $this->stockItemRepository->save()->shouldNotHaveBeenCalled();
        self::assertEquals(
            [
                [
                    'log_level' => 'ERROR',
                    'message'   => 'Product: "PROD30" does not exist.'
                ]
            ],
            $handler->itemMessages
        );
    }

    public function testProductIdIsSetIfNewStockItemIsProducedFromRegistry(): void
    {
        $record = new Record(10, [
            'sku' => 'PROD2',
            'qty' => 10,
        ]);

        $stock = $this->prophesize(StockItemInterface::class);
        $this->stockRegistryProvider->getStockItem(11, 2)->willReturn($stock);

        $stock->getItemId()->willReturn(null);
        $stock->setProductId(11)->shouldBeCalled();
        $stock->setQty(10)->shouldBeCalled();
        $stock->setIsInStock(true)->shouldBeCalled();

        $this->stockItemRepository->save($stock)->shouldBeCalled();

        $this->writer->write(
            $record,
            $this->getObject(ReportItem::class, ['referenceLine' => 100, 'idField' => 'sku', 'idValue' => 100])
        );
    }

    public function testProductStockIsUpdated(): void
    {
        $record = new Record(10, [
            'sku' => 'PROD1',
            'qty' => 10,
        ]);

        $stock = $this->prophesize(StockItemInterface::class);

        $this->stockRegistryProvider->getStockItem(10, 2)->willReturn($stock);

        $stock->getItemId()->willReturn(324);
        $stock->setProductId()->shouldNotBeCalled();
        $stock->setQty(10)->shouldBeCalled();
        $stock->setIsInStock(true)->shouldBeCalled();

        $this->stockItemRepository->save($stock)->shouldBeCalled();

        $this->writer->write(
            $record,
            $this->getObject(ReportItem::class, ['referenceLine' => 100, 'idField' => 'sku', 'idValue' => 100])
        );
    }

    public function testFinishReturnsIdsOfUpdatedStocks(): void
    {
        $record1 = new Record(10, [
            'sku' => 'PROD1',
            'qty' => 10,
        ]);

        $record2 = new Record(11, [
            'sku' => 'PROD2',
            'qty' => 15,
        ]);

        $stock1 = $this->prophesize(StockItemInterface::class);
        $stock2 = $this->prophesize(StockItemInterface::class);

        $this->stockRegistryProvider->getStockItem(10, 2)->willReturn($stock1);
        $this->stockRegistryProvider->getStockItem(11, 2)->willReturn($stock2);

        $stock1->getItemId()->willReturn(324);
        $stock1->setProductId()->shouldNotBeCalled();
        $stock1->setQty(10)->shouldBeCalled();
        $stock1->setIsInStock(true)->shouldBeCalled();

        $this->stockItemRepository->save($stock1)->shouldBeCalled();

        $stock2->getItemId()->willReturn(325);
        $stock2->setProductId()->shouldNotBeCalled();
        $stock2->setQty(15)->shouldBeCalled();
        $stock2->setIsInStock(true)->shouldBeCalled();

        $this->stockItemRepository->save($stock2)->shouldBeCalled();

        $this->writer->write(
            $record1,
            $this->getObject(ReportItem::class, ['referenceLine' => 100, 'idField' => 'sku', 'idValue' => 100])
        );

        $this->writer->write(
            $record2,
            $this->getObject(ReportItem::class, ['referenceLine' => 101, 'idField' => 'sku', 'idValue' => 101])
        );

        $result = $this->writer->finish($this->prophesize(Source::class)->reveal());

        self::assertSame([10, 11], $result->getAffectedIds());
    }

    public function testPrepareDisabledVarnishCacheFlush(): void
    {
        $writer = new StockWriter(
            $this->stockRegistryProvider->reveal(),
            $this->stockItemRepository->reveal(),
            $this->stockConfiguration->reveal(),
            $this->resourceConnection->reveal(),
            $disableEventObserver = new DisableEventObserver()
        );

        $writer->prepare(
            $this->prophesize(Source::class)->reveal(),
            $this->prophesize(Config::class)->reveal(),
        );

        self::assertSame(
            ['clean_cache_by_tags' => ['invalidate_varnish']],
            $disableEventObserver->getDisabledObservers()
        );
    }
}
