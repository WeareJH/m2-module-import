<?php

namespace Jh\ImportTest\Import;

use Jh\Import\Archiver\Archiver;
use Jh\Import\Archiver\Factory;
use Jh\Import\Config;
use Jh\Import\Import\History;
use Jh\Import\Import\Importer;
use Jh\Import\Import\Record;
use Jh\Import\Import\RequiresPreperation;
use Jh\Import\Import\Result;
use Jh\Import\Locker\ImportLockedException;
use Jh\Import\Locker\Locker;
use Jh\Import\Progress\Progress;
use Jh\Import\Report\Handler\CollectingHandler;
use Jh\Import\Report\Handler\DatabaseHandler;
use Jh\Import\Report\Report;
use Jh\Import\Report\ReportFactory;
use Jh\Import\Report\ReportPersister;
use Jh\Import\Source\Iterator;
use Jh\Import\Source\Source;
use Jh\Import\Writer\CollectingWriter;
use Jh\Import\Writer\Writer;
use Jh\UnitTestHelpers\ObjectHelper;
use Magento\Framework\App\State;
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
        $config  = new Config('product', []);
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
            'reportFactory' => $this->reportFactory($config)->reveal(),
            'archiverFactory' => new Factory($om->reveal()),
            'history' => $history->reveal(),
            'writer'  => $writer->reveal()
        ]);

        $writer->prepare(Argument::type(Source::class))->shouldBeCalled();
        $writer->finish(Argument::type(Source::class))->willReturn(new Result([]))->shouldBeCalled();

        $importer->process($config);
    }

    public function testProgressIsAdvancedForEachRecordWithErrors()
    {
        $config   = new Config('product', ['id_field' => 'sku']);
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
            'reportFactory' => $this->reportFactory($config)->reveal(),
            'archiverFactory' => new Factory($om->reveal()),
            'history' => $history->reveal(),
            'progress' => $progress->reveal(),
            'writer' => new CollectingWriter
        ]);

        $progress->start($source, $config)->shouldBeCalled();
        $progress->advance()->shouldBeCalledTimes(3);
        $progress->finish($source)->shouldBeCalled();

        $importer->process($config);
    }

    public function testWriterIsPassedEachRecordAndSourceIsArchived()
    {
        $config         = new Config('product', ['id_field' => 'sku']);
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
            'reportFactory' => $this->reportFactory($config)->reveal(),
            'archiverFactory' => $archiveFactory->reveal(),
            'history' => $history->reveal(),
            'writer' => $writer = new CollectingWriter
        ]);

        $archiveFactory->getArchiverForSource($source, $config)->willReturn($archiver->reveal());

        $importer->process($config);

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
        $config  = new Config('product', ['id_field' => 'sku']);
        $om      = $this->prophesize(ObjectManagerInterface::class);
        $history = $this->prophesize(History::class);
        $history->isImported(Argument::type(Source::class))->willReturn(false);

        $importer = $this->getObject(Importer::class, [
            'source' => $source = Iterator::fromCallable(function () {
                yield ['number' => 1];
                yield ['number' => 2];
                yield ['number' => 3];
            }),
            'reportFactory' => $this->reportFactory($config)->reveal(),
            'archiverFactory' => new Factory($om->reveal()),
            'history' => $history->reveal(),
            'writer' => $writer = new CollectingWriter
        ]);

        $importer->transform(function (Record $record) {
            $record->transform('number', function ($number) {
                return $number * 2;
            });
        });

        $importer->process($config);

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
        $config  = new Config('product', ['id_field' => 'sku']);
        $om      = $this->prophesize(ObjectManagerInterface::class);
        $history = $this->prophesize(History::class);
        $history->isImported(Argument::type(Source::class))->willReturn(false);

        $importer = $this->getObject(Importer::class, [
            'source' => $source = Iterator::fromCallable(function () {
                yield ['number' => 1];
                yield ['number' => 2];
                yield ['number' => 3];
            }),
            'reportFactory' => $this->reportFactory($config)->reveal(),
            'archiverFactory' => new Factory($om->reveal()),
            'history' => $history->reveal(),
            'writer' => $writer = new CollectingWriter
        ]);

        $importer->filter(function (Record $record) {
            return $record->getColumnValue('number') === 2;
        });

        $importer->process($config);

        self::assertEquals(
            [
                ['number' => 2],
            ],
            $writer->getData()
        );
    }

    public function testExceptionsAreAddedAsErrorsAndArchiveFailedIsInvoked()
    {
        $config          = new Config('product', ['id_field' => 'sku']);
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

        $archiveFactory->getArchiverForSource($source, $config)->willReturn($archiver->reveal());
        $report = new Report([$handler = new CollectingHandler], 'product', 'some-source');
        $reportFactory->createFromSourceAndConfig($source, $config)->willReturn($report);

        $importer->process($config);

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
        $config          = new Config('product', ['id_field' => 'sku']);
        $archiveFactory  = $this->prophesize(Factory::class);
        $locker          = $this->prophesize(Locker::class);
        $history         = $this->prophesize(History::class);
        $reportFactory   = $this->prophesize(ReportFactory::class);

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
        $reportFactory->createFromSourceAndConfig($source, $config)->willReturn($report);

        $importer->process($config);

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
        $config          = new Config('product', ['id_field' => 'sku']);
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
        $reportFactory->createFromSourceAndConfig($source, $config)->willReturn($report);

        $importer->process($config);

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
        $config = new Config('product', ['id_field' => 'sku', 'indexers' => ['My\Indexer', 'My\OtherIndexer']]);
        $indexerRegistry = $this->prophesize(IndexerRegistry::class);
        $om              = $this->prophesize(ObjectManagerInterface::class);
        $history         = $this->prophesize(History::class);
        $history->isImported(Argument::type(Source::class))->willReturn(false);

        $importer = $this->getObject(Importer::class, [
            'source' => $source = new Iterator(new \ArrayIterator),
            'reportFactory' => $this->reportFactory($config)->reveal(),
            'archiverFactory' => new Factory($om->reveal()),
            'history' => $history->reveal(),
            'writer' => $writer = new CollectingWriter,
            'indexerRegistry' => $indexerRegistry->reveal(),
        ]);

        $indexer1 = $this->createDisableIndexerMock();
        $indexer2 = $this->createDisableIndexerMock();

        $indexerRegistry->get('My\Indexer')->willReturn($indexer1);
        $indexerRegistry->get('My\OtherIndexer')->willReturn($indexer2);

        $importer->process($config);
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
        $config = new Config('product', ['id_field' => 'sku', 'indexers' => ['My\Indexer', 'My\OtherIndexer']]);
        $indexerRegistry = $this->prophesize(IndexerRegistry::class);
        $om              = $this->prophesize(ObjectManagerInterface::class);
        $history         = $this->prophesize(History::class);
        $history->isImported(Argument::type(Source::class))->willReturn(false);

        $importer = $this->getObject(Importer::class, [
            'source' => $source = new Iterator(new \ArrayIterator),
            'reportFactory' => $this->reportFactory($config)->reveal(),
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

        $importer->process($config);
    }

    public function testIndexersAreCalledWithChunkedAffectedIds()
    {
        $config = new Config('product', ['id_field' => 'sku', 'indexers' => ['My\Indexer', 'My\OtherIndexer']]);
        $indexerRegistry = $this->prophesize(IndexerRegistry::class);
        $om              = $this->prophesize(ObjectManagerInterface::class);
        $history         = $this->prophesize(History::class);
        $history->isImported(Argument::type(Source::class))->willReturn(false);

        $importer = $this->getObject(Importer::class, [
            'source' => $source = new Iterator(new \ArrayIterator),
            'reportFactory' => $this->reportFactory($config)->reveal(),
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

        $importer->process($config);
    }

    private function reportFactory(Config $config)
    {
        $reportFactory = $this->prophesize(ReportFactory::class);
        $reportFactory
            ->createFromSourceAndConfig(
                Argument::type(Source::class),
                $config
            )
            ->willReturn(new Report([], 'product', 'some-source-id'));

        return $reportFactory;
    }

    public function testAddFilterCallsPrepareIfNecessary() : void
    {
        $config  = new Config('product', []);
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
            'reportFactory' => $this->reportFactory($config)->reveal(),
            'archiverFactory' => new Factory($om->reveal()),
            'history' => $history->reveal(),
            'writer'  => $writer->reveal()
        ]);

        $callable = $this->prophesize(\Jh\ImportTest\Asset\CallablePrep::class);
        $callable->prepare($importer)->shouldBeCalled();

        $importer->filter($callable->reveal());
    }

    public function testAddTransformerCallsPrepareIfNecessary() : void
    {
        $config  = new Config('product', []);
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
            'reportFactory' => $this->reportFactory($config)->reveal(),
            'archiverFactory' => new Factory($om->reveal()),
            'history' => $history->reveal(),
            'writer'  => $writer->reveal()
        ]);

        $callable = $this->prophesize(\Jh\ImportTest\Asset\CallablePrep::class);
        $callable->prepare($importer)->shouldBeCalled();

        $importer->transform($callable->reveal());
    }
}
