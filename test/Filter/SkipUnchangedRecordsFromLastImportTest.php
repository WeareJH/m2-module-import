<?php

declare(strict_types=1);

namespace Jh\ImportTest\Filter;

use Jh\Import\Archiver\Csv\Entity\Archive;
use Jh\Import\Archiver\Csv\Entity\ArchiveResource;
use Jh\Import\Config;
use Jh\Import\Entity\ImportHistory;
use Jh\Import\Entity\ImportHistoryResource;
use Jh\Import\Filter\SkipUnchangedRecordsFromLastImport;
use Jh\Import\Import\Record;
use Jh\Import\Source\Iterator;
use Jh\Import\Source\SourceConsumer;
use Jh\Import\Source\SourceFactory;
use Jh\UnitTestHelpers\ObjectHelper;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Framework\ObjectManagerInterface;
use PHPUnit\Framework\TestCase;

class SkipUnchangedRecordsFromLastImportTest extends TestCase
{
    use ObjectHelper;

    /**
     * @var string
     */
    private $tempRoot;

    /**
     * @var DirectoryList
     */
    private $directoryList;

    public function setUp() : void
    {
        $this->tempRoot = sprintf('%s/%s', sys_get_temp_dir(), $this->getName());
        @mkdir($this->tempRoot, 0777, true);

        $this->directoryList = new DirectoryList($this->tempRoot);
    }

    public function testUnchangedRecordsAreSkipped() : void
    {
        $data = [
            ['country' => 'Austria', 'code' => 'AT'],
            ['country' => 'United Kingdom', 'code' => 'UK'],
        ];

        $config = new Config('my-import', ['source' => 'My/Source']);
        $source = Iterator::fromCallable(function () use ($data) {
            yield from $data;
        });

        $resource = $this->prophesize(AbstractDb::class);
        $resource->getIdFieldName()->willReturn('id');

        $importHistory = $this->getObject(ImportHistory::class, ['resource' => $resource->reveal()]);
        $importHistory->setData(['id' => 10, 'source_id' => 'AE53J6FFSTT33H']);

        $importHistoryResource = $this->prophesize(ImportHistoryResource::class);
        $importHistoryResource->getLastImportByName('my-import')->willReturn($importHistory);

        $file = 'jh_import/archived/file-05062020123739.csv';
        $archive = $this->getObject(Archive::class, ['resource' => $resource->reveal()]);
        $archive->setData(['id' => 10, 'deleted' => false, 'archived' => false, 'file_location' => $file]);

        $archiveResource = $this->prophesize(ArchiveResource::class);
        $archiveResource->getBySourceId('AE53J6FFSTT33H')->willReturn($archive);

        $om = $this->prophesize(ObjectManagerInterface::class);
        $om->create('My/Source', ['file' => sprintf('%s/var/%s', $this->tempRoot, $file)])->willReturn($source);

        $filter = new SkipUnchangedRecordsFromLastImport(
            $importHistoryResource->reveal(),
            $archiveResource->reveal(),
            $this->directoryList,
            new SourceFactory($om->reveal()),
            new SourceConsumer(),
        );

        $filter->prepare($config);

        self::assertFalse($filter->__invoke(new Record(0, ['country' => 'Austria', 'code' => 'AT'])));
        self::assertFalse($filter->__invoke(new Record(0, ['country' => 'United Kingdom', 'code' => 'UK'])));
        self::assertTrue($filter->__invoke(new Record(0, ['country' => 'Turkey', 'code' => 'TR'])));
        self::assertTrue($filter->__invoke(new Record(0, ['country' => 'France', 'code' => 'FR'])));
    }

    public function testAllRecordsAreProcessedIfNoDuplicates() : void
    {
        $config = new Config('my-import', ['source' => 'My/Source']);
        $source = new Iterator(new \ArrayIterator([]));

        $resource = $this->prophesize(AbstractDb::class);
        $resource->getIdFieldName()->willReturn('id');

        $importHistory = $this->getObject(ImportHistory::class, ['resource' => $resource->reveal()]);
        $importHistory->setData(['id' => 10, 'source_id' => 'AE53J6FFSTT33H']);

        $importHistoryResource = $this->prophesize(ImportHistoryResource::class);
        $importHistoryResource->getLastImportByName('my-import')->willReturn($importHistory);

        $file = 'jh_import/archived/file-05062020123739.csv';
        $archive = $this->getObject(Archive::class, ['resource' => $resource->reveal()]);
        $archive->setData(['id' => 10, 'deleted' => false, 'archived' => false, 'file_location' => $file]);

        $archiveResource = $this->prophesize(ArchiveResource::class);
        $archiveResource->getBySourceId('AE53J6FFSTT33H')->willReturn($archive);

        $om = $this->prophesize(ObjectManagerInterface::class);
        $om->create('My/Source', ['file' => sprintf('%s/var/%s', $this->tempRoot, $file)])->willReturn($source);

        $filter = new SkipUnchangedRecordsFromLastImport(
            $importHistoryResource->reveal(),
            $archiveResource->reveal(),
            $this->directoryList,
            new SourceFactory($om->reveal()),
            new SourceConsumer(),
        );

        $filter->prepare($config);

        self::assertTrue($filter->__invoke(new Record(0, ['country' => 'Austria', 'code' => 'AT'])));
        self::assertTrue($filter->__invoke(new Record(0, ['country' => 'United Kingdom', 'code' => 'UK'])));
        self::assertTrue($filter->__invoke(new Record(0, ['country' => 'Turkey', 'code' => 'TR'])));
        self::assertTrue($filter->__invoke(new Record(0, ['country' => 'France', 'code' => 'FR'])));
    }

    public function testAllRecordsAreProcessedIfNoPreviousImport() : void
    {
        $config = new Config('my-import', ['source' => 'My/Source']);

        $resource = $this->prophesize(AbstractDb::class);
        $resource->getIdFieldName()->willReturn('id');

        $importHistory = $this->getObject(ImportHistory::class, ['resource' => $resource->reveal()]);

        $importHistoryResource = $this->prophesize(ImportHistoryResource::class);
        $importHistoryResource->getLastImportByName('my-import')->willReturn($importHistory);

        $filter = new SkipUnchangedRecordsFromLastImport(
            $importHistoryResource->reveal(),
            $this->prophesize(ArchiveResource::class)->reveal(),
            $this->directoryList,
            new SourceFactory($this->prophesize(ObjectManagerInterface::class)->reveal()),
            new SourceConsumer(),
        );

        $filter->prepare($config);

        self::assertTrue($filter->__invoke(new Record(0, ['country' => 'Austria', 'code' => 'AT'])));
        self::assertTrue($filter->__invoke(new Record(0, ['country' => 'United Kingdom', 'code' => 'UK'])));
        self::assertTrue($filter->__invoke(new Record(0, ['country' => 'Turkey', 'code' => 'TR'])));
        self::assertTrue($filter->__invoke(new Record(0, ['country' => 'France', 'code' => 'FR'])));
    }

    public function testAllRecordsAreProcessedIfNoPreviousFileExists() : void
    {
        $config = new Config('my-import', ['source' => 'My/Source']);

        $resource = $this->prophesize(AbstractDb::class);
        $resource->getIdFieldName()->willReturn('id');

        $importHistory = $this->getObject(ImportHistory::class, ['resource' => $resource->reveal()]);
        $importHistory->setData(['id' => 10, 'source_id' => 'AE53J6FFSTT33H']);

        $importHistoryResource = $this->prophesize(ImportHistoryResource::class);
        $importHistoryResource->getLastImportByName('my-import')->willReturn($importHistory);

        $file = 'jh_import/archived/file-05062020123739.csv';
        $archive = $this->getObject(Archive::class, ['resource' => $resource->reveal()]);
        $archive->setData(['id' => 10, 'deleted' => true, 'archived' => false, 'file_location' => $file]);

        $archiveResource = $this->prophesize(ArchiveResource::class);
        $archiveResource->getBySourceId('AE53J6FFSTT33H')->willReturn($archive);

        $filter = new SkipUnchangedRecordsFromLastImport(
            $importHistoryResource->reveal(),
            $archiveResource->reveal(),
            $this->directoryList,
            new SourceFactory($this->prophesize(ObjectManagerInterface::class)->reveal()),
            new SourceConsumer(),
        );

        $filter->prepare($config);

        self::assertTrue($filter->__invoke(new Record(0, ['country' => 'Austria', 'code' => 'AT'])));
        self::assertTrue($filter->__invoke(new Record(0, ['country' => 'United Kingdom', 'code' => 'UK'])));
        self::assertTrue($filter->__invoke(new Record(0, ['country' => 'Turkey', 'code' => 'TR'])));
        self::assertTrue($filter->__invoke(new Record(0, ['country' => 'France', 'code' => 'FR'])));
    }
}
