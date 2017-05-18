<?php

namespace Jh\ImportTest\Import;

use Jh\Import\Archiver\Archiver;
use Jh\Import\Archiver\Factory;
use Jh\Import\Config;
use Jh\Import\Import\History;
use Jh\Import\Import\Importer;
use Jh\Import\Import\Record;
use Jh\Import\Import\Result;
use Jh\Import\Locker\ImportLockedException;
use Jh\Import\Locker\Locker;
use Jh\Import\Progress\Progress;
use Jh\Import\Report\Handler\CollectingHandler;
use Jh\Import\Report\Report;
use Jh\Import\Report\ReportFactory;
use Jh\Import\Report\ReportPersister;
use Jh\Import\Source\Iterator;
use Jh\Import\Source\Source;
use Jh\Import\Writer\CollectingWriter;
use Jh\Import\Writer\Writer;
use Jh\UnitTestHelpers\ObjectHelper;
use Magento\Framework\Indexer\IndexerRegistry;
use Magento\Framework\Mview\View\StateInterface;
use Magento\Framework\ObjectManagerInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;

/**
 * @author Aydin Hassan <aydin@wearejh.com>
 */
class ImporterTest extends TestCase
{
    use ObjectHelper;

    public function testWriterIsPreparedAndFinished()
    {
        $om      = $this->prophesize(ObjectManagerInterface::class);
        $writer  = $this->prophesize(Writer::class);
        $history = $this->prophesize(History::class);
        $history->isImported(Argument::type(Source::class))->willReturn(false);

        $importer = $this->getObject(Importer::class, [
            'source' => Iterator::fromCallable(function () {
                yield [1];
                yield [2];
                yield [3];
            }),
            'reportFactory' => $this->getObject(ReportFactory::class),
            'archiverFactory' => new Factory($om->reveal()),
            'history' => $history->reveal(),
            'writer'  => $writer->reveal()
        ]);

        $writer->prepare(Argument::type(Source::class))->shouldBeCalled();
        $writer->finish(Argument::type(Source::class))->willReturn(new Result([]))->shouldBeCalled();

        $importer->process(new Config('product', []));
    }
    
    public function testProgressIsAdvancedForEachRecordWithErrors()
    {
        $om       = $this->prophesize(ObjectManagerInterface::class);
        $progress = $this->prophesize(Progress::class);
        $history  = $this->prophesize(History::class);
        $history->isImported(Argument::type(Source::class))->willReturn(false);

        $importer = $this->getObject(Importer::class, [
            'source' => $source = Iterator::fromCallable(function () {
                yield [1];
                yield [2];
                yield [3];
            }),
            'reportFactory' => $this->getObject(ReportFactory::class),
            'archiverFactory' => new Factory($om->reveal()),
            'history' => $history->reveal(),
            'progress' => $progress->reveal(),
            'writer' => new CollectingWriter
        ]);

        $config = new Config('product', ['id_field' => 'sku']);
        $progress->start($source, $config)->shouldBeCalled();
        $progress->advance()->shouldBeCalledTimes(3);
        $progress->finish($source)->shouldBeCalled();

        $importer->process($config);
    }

    public function testWriterIsPassedEachRecordAndSourceIsArchived()
    {
        $archiveFactory = $this->prophesize(Factory::class);
        $history        = $this->prophesize(History::class);
        $archiver       = $this->prophesize(Archiver::class);
        $history->isImported(Argument::type(Source::class))->willReturn(false);

        $importer = $this->getObject(Importer::class, [
            'source' => $source = Iterator::fromCallable(function () {
                yield ['number' => 1];
                yield ['number' => 2];
                yield ['number' => 3];
            }),
            'reportFactory' => $this->getObject(ReportFactory::class),
            'archiverFactory' => $archiveFactory->reveal(),
            'history' => $history->reveal(),
            'writer' => $writer = new CollectingWriter
        ]);

        $archiveFactory->getArchiverForSource($source)->willReturn($archiver->reveal());

        $importer->process(new Config('product', ['id_field' => 'sku']));

        self::assertEquals(
            [
                ['number' => 1],
                ['number' => 2],
                ['number' => 3]
            ],
            $writer->getData()
        );

        $archiver->successful()->shouldHaveBeenCalled();
    }

    public function testTransformersRunOnEveryRecord()
    {
        $om      = $this->prophesize(ObjectManagerInterface::class);
        $history = $this->prophesize(History::class);
        $history->isImported(Argument::type(Source::class))->willReturn(false);

        $importer = $this->getObject(Importer::class, [
            'source' => $source = Iterator::fromCallable(function () {
                yield ['number' => 1];
                yield ['number' => 2];
                yield ['number' => 3];
            }),
            'reportFactory' => $this->getObject(ReportFactory::class),
            'archiverFactory' => new Factory($om->reveal()),
            'history' => $history->reveal(),
            'writer' => $writer = new CollectingWriter
        ]);

        $importer->transform(function (Record $record) {
            $record->transform('number', function ($number) {
                return $number * 2;
            });
        });

        $importer->process(new Config('product', ['id_field' => 'sku']));

        self::assertEquals(
            [
                ['number' => 2],
                ['number' => 4],
                ['number' => 6]
            ],
            $writer->getData()
        );
    }

    public function testFiltersRunOnEveryRecord()
    {
        $om      = $this->prophesize(ObjectManagerInterface::class);
        $history = $this->prophesize(History::class);
        $history->isImported(Argument::type(Source::class))->willReturn(false);

        $importer = $this->getObject(Importer::class, [
            'source' => $source = Iterator::fromCallable(function () {
                yield ['number' => 1];
                yield ['number' => 2];
                yield ['number' => 3];
            }),
            'reportFactory' => $this->getObject(ReportFactory::class),
            'archiverFactory' => new Factory($om->reveal()),
            'history' => $history->reveal(),
            'writer' => $writer = new CollectingWriter
        ]);

        $importer->filter(function (Record $record) {
            return $record->getColumnValue('number') === 2;
        });

        $importer->process(new Config('product', ['id_field' => 'sku']));

        self::assertEquals(
            [
                ['number' => 2],
            ],
            $writer->getData()
        );
    }

    public function testExceptionsAreAddedAsErrorsAndArchiveFailedIsInvoked()
    {
        $archiveFactory  = $this->prophesize(Factory::class);
        $archiver        = $this->prophesize(Archiver::class);
        $history = $this->prophesize(History::class);
        $history->isImported(Argument::type(Source::class))->willReturn(false);

        $reportFactory = $this->prophesize(ReportFactory::class);

        $importer = $this->getObject(Importer::class, [
            'source' => $source = Iterator::fromCallable(function () {
                yield ['number' => 1];
                yield ['number' => 2];
                throw new \Exception('Some Catastrophic error');
            }),
            'history' => $history->reveal(),
            'reportFactory'   => $reportFactory->reveal(),
            'archiverFactory' => $archiveFactory->reveal(),
            'writer' => new CollectingWriter
        ]);

        $archiveFactory->getArchiverForSource($source)->willReturn($archiver->reveal());
        $report = new Report([$handler = new CollectingHandler], 'product', 'some-source');
        $reportFactory->createFromSourceAndName($source, 'product')->willReturn($report);

        $importer->process(new Config('product', ['id_field' => 'sku']));

        $archiver->failed()->shouldHaveBeenCalled();
        self::assertEquals(
            [
                [
                    'log_level' => 'ERROR',
                    'message' => 'Could not read data from source. Error: "Some Catastrophic error"'
                ]
            ],
            $handler->messages
        );
    }

    public function testImporterIsSkippedIfItIsLocked()
    {
        $reportFactory   = $this->prophesize(ReportFactory::class);
        $archiveFactory  = $this->prophesize(Factory::class);
        $locker          = $this->prophesize(Locker::class);
        $history         = $this->prophesize(History::class);

        $importer = $this->getObject(Importer::class, [
            'source' => $source = Iterator::fromCallable(function () {
                yield ['number' => 1];
                yield ['number' => 2];
            }),
            'reportFactory' => $reportFactory->reveal(),
            'history' => $history->reveal(),
            'locker' => $locker->reveal(),
            'archiverFactory' => $archiveFactory->reveal(),
            'writer' => new CollectingWriter
        ]);

        $history->isImported($source)->willReturn(false);
        $locker->lock('product')->willThrow(ImportLockedException::fromName('product'));

        $report = new Report([$handler = new CollectingHandler], 'product', 'some-source');
        $reportFactory->createFromSourceAndName($source, 'product')->willReturn($report);

        $importer->process(new Config('product', ['id_field' => 'sku']));


        $archiveFactory->getArchiverForSource()->shouldNotHaveBeenCalled();
        self::assertEquals(
            [
                [
                    'log_level' => 'ERROR',
                    'message' => 'Import with name "product" is locked.'
                ]
            ],
            $handler->messages
        );
    }

    public function testImportIsSkippedIfSourceAlreadyImported()
    {
        $reportFactory   = $this->prophesize(ReportFactory::class);
        $archiveFactory  = $this->prophesize(Factory::class);
        $locker          = $this->prophesize(Locker::class);
        $history         = $this->prophesize(History::class);

        $importer = $this->getObject(Importer::class, [
            'source' => $source = Iterator::fromCallable(function () {
                yield ['number' => 1];
                yield ['number' => 2];
            }),
            'reportFactory' => $reportFactory->reveal(),
            'history' => $history->reveal(),
            'locker' => $locker->reveal(),
            'archiverFactory' => $archiveFactory->reveal(),
            'writer' => new CollectingWriter
        ]);

        $history->isImported($source)->willReturn(true);

        $report = new Report([$handler = new CollectingHandler], 'product', 'some-source');
        $reportFactory->createFromSourceAndName($source, 'product')->willReturn($report);

        $importer->process(new Config('product', ['id_field' => 'sku']));

        $archiveFactory->getArchiverForSource()->shouldNotHaveBeenCalled();
        self::assertEquals(
            [
                [
                    'log_level' => 'ERROR',
                    'message' => 'This import source has already been imported.'
                ]
            ],
            $handler->messages
        );
    }

    public function testIndexersAreDisabledIfSpecifiedInConfig()
    {
        $indexerRegistry = $this->prophesize(IndexerRegistry::class);
        $om = $this->prophesize(ObjectManagerInterface::class);
        $history = $this->prophesize(History::class);
        $history->isImported(Argument::type(Source::class))->willReturn(false);

        $importer = $this->getObject(Importer::class, [
            'source' => $source = new Iterator(new \ArrayIterator),
            'reportFactory' => $this->getObject(ReportFactory::class),
            'archiverFactory' => new Factory($om->reveal()),
            'history' => $history->reveal(),
            'writer' => $writer = new CollectingWriter,
            'indexerRegistry' => $indexerRegistry->reveal(),
        ]);

        $indexer1 = $this->createDisableIndexerMock();
        $indexer2 = $this->createDisableIndexerMock();

        $indexerRegistry->get('My\Indexer')->willReturn($indexer1);
        $indexerRegistry->get('My\OtherIndexer')->willReturn($indexer2);

        $importer->process(
            new Config('product', ['id_field' => 'sku', 'indexers' => ['My\Indexer', 'My\OtherIndexer']])
        );
    }

    private function createDisableIndexerMock()
    {
        $state = $this->prophesize(StateInterface::class);
        $state->setMode(StateInterface::MODE_ENABLED)->shouldBeCalled();

        $view = $this->prophesize(\Magento\Framework\Mview\ViewInterface::class);
        $view->getState()->willReturn($state);

        $indexer = $this->prophesize(\Magento\Framework\Indexer\IndexerInterface::class);
        $indexer->getView()->willReturn($view);

        return $indexer;
    }

    public function testIndexersAreCalledWithAffectedIds()
    {
        $indexerRegistry = $this->prophesize(IndexerRegistry::class);
        $om = $this->prophesize(ObjectManagerInterface::class);
        $history = $this->prophesize(History::class);
        $history->isImported(Argument::type(Source::class))->willReturn(false);

        $importer = $this->getObject(Importer::class, [
            'source' => $source = new Iterator(new \ArrayIterator),
            'reportFactory' => $this->getObject(ReportFactory::class),
            'archiverFactory' => new Factory($om->reveal()),
            'history' => $history->reveal(),
            'writer' => $writer = new CollectingWriter,
            'indexerRegistry' => $indexerRegistry->reveal(),
        ]);

        $indexer1 = $this->createDisableIndexerMock();
        $indexer2 = $this->createDisableIndexerMock();

        $indexer1->reindexList([1, 2, 3, 4, 5])->shouldBeCalled();
        $indexer2->reindexList([1, 2, 3, 4, 5])->shouldBeCalled();

        $indexerRegistry->get('My\Indexer')->willReturn($indexer1);
        $indexerRegistry->get('My\OtherIndexer')->willReturn($indexer2);

        $writer->setAffectedIds([1, 2, 3, 4, 5]);

        $importer->process(
            new Config('product', ['id_field' => 'sku', 'indexers' => ['My\Indexer', 'My\OtherIndexer']])
        );
    }

    public function testIndexersAreCalledWithChunkedAffectedIds()
    {
        $indexerRegistry = $this->prophesize(IndexerRegistry::class);
        $om = $this->prophesize(ObjectManagerInterface::class);
        $history = $this->prophesize(History::class);
        $history->isImported(Argument::type(Source::class))->willReturn(false);

        $importer = $this->getObject(Importer::class, [
            'source' => $source = new Iterator(new \ArrayIterator),
            'reportFactory' => $this->getObject(ReportFactory::class),
            'archiverFactory' => new Factory($om->reveal()),
            'history' => $history->reveal(),
            'writer' => $writer = new CollectingWriter,
            'indexerRegistry' => $indexerRegistry->reveal(),
        ]);

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

        $writer->setAffectedIds(range(0, 2000));

        $importer->process(
            new Config('product', ['id_field' => 'sku', 'indexers' => ['My\Indexer', 'My\OtherIndexer']])
        );
    }
}
