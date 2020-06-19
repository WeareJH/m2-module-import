<?php

namespace Jh\ImportTest\Import;

use Jh\Import\Archiver\Archiver;
use Jh\Import\Archiver\Factory;
use Jh\Import\Config;
use Jh\Import\Import\History;
use Jh\Import\Import\Importer;
use Jh\Import\Import\Indexer;
use Jh\Import\Import\Record;
use Jh\Import\Import\RequiresPreperation;
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
use Magento\Framework\ObjectManagerInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;

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

    public function testIndexersAreDisabledAtStartAndIndexedAtEnd(): void
    {
        $config  = new Config('product', []);
        $om      = $this->prophesize(ObjectManagerInterface::class);
        $writer  = $this->prophesize(Writer::class);
        $history = $this->prophesize(History::class);
        $history->isImported(Argument::type(Source::class))->willReturn(false);

        $indexer = $this->prophesize(Indexer::class);

        $importer = $this->getObject(Importer::class, [
            'source' => Iterator::fromCallable(function () {
                yield [1];
                yield [2];
                yield [3];
            }),
            'reportFactory' => $this->reportFactory($config)->reveal(),
            'archiverFactory' => new Factory($om->reveal()),
            'history' => $history->reveal(),
            'writer'  => $writer->reveal(),
            'indexer' => $indexer->reveal()
        ]);

        $result = new Result([1, 2, 3]);

        $writer->prepare(Argument::type(Source::class))->shouldBeCalled();
        $writer->finish(Argument::type(Source::class))->willReturn($result)->shouldBeCalled();

        $importer->process($config);

        $indexer->disable($config)->shouldHaveBeenCalled();
        $indexer->index($config, $result)->shouldHaveBeenCalled();
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
            'writer' => new CollectingWriter()
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
            'writer' => $writer = new CollectingWriter()
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
            'writer' => $writer = new CollectingWriter()
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
            'writer' => $writer = new CollectingWriter()
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
            'writer' => new CollectingWriter()
        ]);

        $archiveFactory->getArchiverForSource($source, $config)->willReturn($archiver->reveal());
        $report = new Report([$handler = new CollectingHandler()], 'product', 'some-source');
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
            'writer' => new CollectingWriter()
        ]);

        $history->isImported($source)->willReturn(false);
        $locker->lock('product')->willThrow(ImportLockedException::fromName('product'));

        $report = new Report([$handler = new CollectingHandler()], 'product', 'some-source');
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
            'writer' => new CollectingWriter()
        ]);

        $history->isImported($source)->willReturn(true);

        $report = new Report([$handler = new CollectingHandler()], 'product', 'some-source');
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

    private function reportFactory(Config $config): ObjectProphecy
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

    public function testAddFilterCallsPrepareIfNecessary(): void
    {
        $config  = new Config('product', []);
        $om      = $this->prophesize(ObjectManagerInterface::class);
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
            'writer' => new CollectingWriter(),
        ]);

        $callable = $this->prophesize(\Jh\ImportTest\Asset\CallablePrep::class);
        $callable->prepare($config)->shouldBeCalled();

        $importer->filter($callable->reveal());
        $importer->process($config);
    }

    public function testAddTransformerCallsPrepareIfNecessary(): void
    {
        $config  = new Config('product', []);
        $om      = $this->prophesize(ObjectManagerInterface::class);
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
            'writer' => new CollectingWriter(),
        ]);

        $callable = $this->prophesize(\Jh\ImportTest\Asset\CallablePrep::class);
        $callable->prepare($config)->shouldBeCalled();

        $importer->transform($callable->reveal());
        $importer->process($config);
    }
}
