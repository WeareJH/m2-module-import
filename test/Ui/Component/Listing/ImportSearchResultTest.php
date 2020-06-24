<?php

namespace Jh\ImportTest\Ui\Component\Listing;

use Jh\Import\Config\Data;
use Jh\Import\Locker\Locker;
use Jh\Import\Ui\Component\Listing\ImportSearchResult;
use Magento\Framework\Api\AttributeValueFactory;
use Magento\Framework\Api\Search\AggregationInterface;
use Magento\Framework\Api\Search\SearchCriteriaInterface;
use Magento\Framework\Data\Collection\EntityFactoryInterface;
use Magento\Framework\Serialize\Serializer\Serialize;
use Magento\Framework\View\Element\UiComponent\DataProvider\Document;
use PHPUnit\Framework\TestCase;
use Magento\Framework\Config\CacheInterface;
use Magento\Framework\Config\ReaderInterface;
use Prophecy\Argument;

class ImportSearchResultTest extends TestCase
{
    public function testGetAllItems(): void
    {
        $reader = $this->prophesize(ReaderInterface::class);
        $cache  = $this->prophesize(CacheInterface::class);

        $imports = [
            'product' => ['type' => 'files'],
            'stock' => ['type' => 'files']
        ];

        $cache->load('cache-id')->willReturn(serialize($imports))->shouldBeCalled();
        $config  = new Data($reader->reveal(), $cache->reveal(), 'cache-id', new Serialize());

        $avFactory = $this->prophesize(AttributeValueFactory::class);

        $entityFactory = $this->prophesize(EntityFactoryInterface::class);
        $entityFactory->create(Document::class)
            ->willReturn(new Document($avFactory->reveal()), new Document($avFactory->reveal()));

        $locker = $this->prophesize(Locker::class);
        $locker->locked('product')->willReturn(true);
        $locker->locked('stock')->willReturn(false);

        $searchResult = new ImportSearchResult($entityFactory->reveal(), $config, $locker->reveal());

        $items = $searchResult->getItems();

        self::assertCount(2, $items);
        self::assertContainsOnly(Document::class, $items);
        self::assertEquals('product', $items[0]->getData('name'));
        self::assertEquals('files', $items[0]->getData('type'));
        self::assertEquals('stock', $items[1]->getData('name'));
        self::assertEquals('files', $items[1]->getData('type'));
        self::assertEquals('Locked', $items[0]->getData('lock_status'));
        self::assertEquals('Not locked', $items[1]->getData('lock_status'));
    }

    public function testSetItemsIgnoresNullValue(): void
    {
        $reader = $this->prophesize(ReaderInterface::class);
        $cache  = $this->prophesize(CacheInterface::class);

        $imports = [
            'product' => ['type' => 'files'],
            'stock' => ['type' => 'files']
        ];

        $cache->load('cache-id')->willReturn(serialize($imports))->shouldBeCalled();
        $config  = new Data($reader->reveal(), $cache->reveal(), 'cache-id', new Serialize());

        $avFactory = $this->prophesize(AttributeValueFactory::class);

        $entityFactory = $this->prophesize(EntityFactoryInterface::class);
        $entityFactory->create(Document::class)
            ->willReturn(new Document($avFactory->reveal()), new Document($avFactory->reveal()));

        $locker = $this->prophesize(Locker::class);
        $locker->locked(Argument::any())->willReturn(false);

        $searchResult = new ImportSearchResult($entityFactory->reveal(), $config, $locker->reveal());

        $items = $searchResult->getItems();

        self::assertCount(2, $items);
        self::assertContainsOnly(Document::class, $items);
        self::assertEquals('product', $items[0]->getData('name'));
        self::assertEquals('files', $items[0]->getData('type'));
        self::assertEquals('stock', $items[1]->getData('name'));
        self::assertEquals('files', $items[1]->getData('type'));

        $searchResult->setItems(null);

        self::assertCount(2, $items);
        self::assertContainsOnly(Document::class, $items);
        self::assertEquals('product', $items[0]->getData('name'));
        self::assertEquals('files', $items[0]->getData('type'));
        self::assertEquals('stock', $items[1]->getData('name'));
        self::assertEquals('files', $items[1]->getData('type'));
    }

    public function testSetItems(): void
    {
        $reader = $this->prophesize(ReaderInterface::class);
        $cache  = $this->prophesize(CacheInterface::class);

        $imports = [
            'product' => ['type' => 'files'],
            'stock' => ['type' => 'files']
        ];

        $cache->load('cache-id')->willReturn(serialize($imports))->shouldBeCalled();
        $config  = new Data($reader->reveal(), $cache->reveal(), 'cache-id', new Serialize());

        $avFactory = $this->prophesize(AttributeValueFactory::class);

        $entityFactory = $this->prophesize(EntityFactoryInterface::class);
        $entityFactory->create(Document::class)
            ->willReturn(new Document($avFactory->reveal()), new Document($avFactory->reveal()));

        $locker = $this->prophesize(Locker::class);
        $locker->locked(Argument::any())->willReturn(false);

        $searchResult = new ImportSearchResult($entityFactory->reveal(), $config, $locker->reveal());

        $items = $searchResult->getItems();

        self::assertCount(2, $items);
        self::assertContainsOnly(Document::class, $items);
        self::assertEquals('product', $items[0]->getData('name'));
        self::assertEquals('files', $items[0]->getData('type'));
        self::assertEquals('stock', $items[1]->getData('name'));
        self::assertEquals('files', $items[1]->getData('type'));

        $searchResult->setItems([$item = new Document($avFactory->reveal())]);

        self::assertSame([$item], $searchResult->getItems());
    }

    public function testGetSetAggregations(): void
    {
        $reader = $this->prophesize(ReaderInterface::class);
        $cache  = $this->prophesize(CacheInterface::class);

        $imports = [
            'product' => ['type' => 'files'],
            'stock' => ['type' => 'files']
        ];

        $cache->load('cache-id')->willReturn(serialize($imports))->shouldBeCalled();
        $config  = new Data($reader->reveal(), $cache->reveal(), 'cache-id', new Serialize());

        $avFactory = $this->prophesize(AttributeValueFactory::class);

        $entityFactory = $this->prophesize(EntityFactoryInterface::class);
        $entityFactory->create(Document::class)
            ->willReturn(new Document($avFactory->reveal()), new Document($avFactory->reveal()));

        $locker = $this->prophesize(Locker::class);
        $locker->locked(Argument::any())->willReturn(false);

        $searchResult = new ImportSearchResult($entityFactory->reveal(), $config, $locker->reveal());

        $aggregation = $this->prophesize(AggregationInterface::class)->reveal();

        $searchResult->setAggregations($aggregation);
        self::assertSame($aggregation, $searchResult->getAggregations());
    }

    public function testGetSetSearchCriteria(): void
    {
        $reader = $this->prophesize(ReaderInterface::class);
        $cache  = $this->prophesize(CacheInterface::class);

        $imports = [
            'product' => ['type' => 'files'],
            'stock' => ['type' => 'files']
        ];

        $cache->load('cache-id')->willReturn(serialize($imports))->shouldBeCalled();
        $config  = new Data($reader->reveal(), $cache->reveal(), 'cache-id', new Serialize());

        $avFactory = $this->prophesize(AttributeValueFactory::class);

        $entityFactory = $this->prophesize(EntityFactoryInterface::class);
        $entityFactory->create(Document::class)
            ->willReturn(new Document($avFactory->reveal()), new Document($avFactory->reveal()));

        $locker = $this->prophesize(Locker::class);
        $locker->locked(Argument::any())->willReturn(false);

        $searchResult = new ImportSearchResult($entityFactory->reveal(), $config, $locker->reveal());

        $searchCriteria = $this->prophesize(SearchCriteriaInterface::class)->reveal();

        $searchResult->setSearchCriteria($searchCriteria);
        self::assertSame($searchCriteria, $searchResult->getSearchCriteria());
    }

    public function testGetSetTotalCount(): void
    {
        $reader = $this->prophesize(ReaderInterface::class);
        $cache  = $this->prophesize(CacheInterface::class);

        $imports = [
            'product' => ['type' => 'files'],
            'stock' => ['type' => 'files']
        ];

        $cache->load('cache-id')->willReturn(serialize($imports))->shouldBeCalled();
        $config  = new Data($reader->reveal(), $cache->reveal(), 'cache-id', new Serialize());

        $avFactory = $this->prophesize(AttributeValueFactory::class);

        $entityFactory = $this->prophesize(EntityFactoryInterface::class);
        $entityFactory->create(Document::class)
            ->willReturn(new Document($avFactory->reveal()), new Document($avFactory->reveal()));

        $locker = $this->prophesize(Locker::class);
        $locker->locked(Argument::any())->willReturn(false);

        $searchResult = new ImportSearchResult($entityFactory->reveal(), $config, $locker->reveal());

        self::assertEquals(2, $searchResult->getTotalCount());

        $searchResult->setTotalCount(3);

        self::assertEquals(3, $searchResult->getTotalCount());
    }
}
