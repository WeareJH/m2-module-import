<?php

declare(strict_types=1);

namespace Jh\ImportTest\Import;

use Jh\Import\Config;
use Jh\Import\Import\Indexer;
use Jh\Import\Import\Result;
use Magento\Framework\Indexer\IndexerInterface;
use Magento\Framework\Indexer\IndexerRegistry;
use Magento\Framework\Mview\ViewInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\Console\Output\NullOutput;
use Magento\Framework\Mview\View\StateInterface;

class IndexerTest extends TestCase
{
    public function testIndexersAreDisabledIfSpecifiedInConfig(): void
    {
        $config = new Config('product', ['id_field' => 'sku', 'indexers' => ['My\Indexer', 'My\OtherIndexer']]);
        $indexerRegistry = $this->prophesize(IndexerRegistry::class);

        $indexer = new Indexer($indexerRegistry->reveal(), new NullOutput());

        $indexer1 = $this->createDisableIndexerMock();
        $indexer2 = $this->createDisableIndexerMock();

        $indexerRegistry->get('My\Indexer')->willReturn($indexer1);
        $indexerRegistry->get('My\OtherIndexer')->willReturn($indexer2);

        $indexer->disable($config);
    }

    public function testIndexersAreCalledWithAffectedIds(): void
    {
        $config = new Config('product', ['id_field' => 'sku', 'indexers' => ['My\Indexer', 'My\OtherIndexer']]);
        $indexerRegistry = $this->prophesize(IndexerRegistry::class);

        $indexer = new Indexer($indexerRegistry->reveal(), new NullOutput());

        $indexer1 = $this->createDisableIndexerMock();
        $indexer2 = $this->createDisableIndexerMock();

        $indexer1->reindexList([1, 2, 3, 4, 5])->shouldBeCalled();
        $indexer2->reindexList([1, 2, 3, 4, 5])->shouldBeCalled();

        $indexerRegistry->get('My\Indexer')->willReturn($indexer1);
        $indexerRegistry->get('My\OtherIndexer')->willReturn($indexer2);

        $indexer->disable($config);
        $indexer->index($config, new Result([1, 2, 3, 4, 5]));
    }

    public function testIndexersAreCalledWithChunkedAffectedIds(): void
    {
        $config = new Config('product', ['id_field' => 'sku', 'indexers' => ['My\Indexer', 'My\OtherIndexer']]);
        $indexerRegistry = $this->prophesize(IndexerRegistry::class);

        $indexer = new Indexer($indexerRegistry->reveal(), new NullOutput());

        $indexer1 = $this->createDisableIndexerMock();
        $indexer2 = $this->createDisableIndexerMock();

        $indexer1->reindexList(range(0, 999))->shouldBeCalled();
        $indexer1->reindexList(range(1000, 1999))->shouldBeCalled();
        $indexer1->reindexList([2000])->shouldBeCalled();
        $indexer2->reindexList(range(0, 999))->shouldBeCalled();
        $indexer2->reindexList(range(1000, 1999))->shouldBeCalled();
        $indexer2->reindexList([2000])->shouldBeCalled();

        $indexerRegistry->get('My\Indexer')->willReturn($indexer1);
        $indexerRegistry->get('My\OtherIndexer')->willReturn($indexer2);

        $indexer->disable($config);
        $indexer->index($config, new Result(range(0, 2000)));
    }

    private function createDisableIndexerMock(): ObjectProphecy
    {
        $state = $this->prophesize(StateInterface::class);
        $state->setMode(StateInterface::MODE_ENABLED)->shouldBeCalled();

        $view = $this->prophesize(ViewInterface::class);
        $view->getState()->willReturn($state);

        $indexer = $this->prophesize(IndexerInterface::class);
        $indexer->getView()->willReturn($view);

        return $indexer;
    }
}
