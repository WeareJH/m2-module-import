<?php

namespace Jh\ImportTest\Cron;

use Jh\Import\Config\Data;
use Jh\Import\Cron\ArchiveFiles;
use phpmock\MockBuilder;
use PHPUnit\Framework\TestCase;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem\Directory\WriteFactory;
use Magento\Framework\Filesystem\DriverPool;
use Symfony\Component\Filesystem\Filesystem;
use Magento\Framework\Config\ReaderInterface;
use Magento\Framework\Config\CacheInterface;

class ArchiveFilesTest extends TestCase
{
    /**
     * @var string
     */
    private $tempDirectory;

    /**
     * @var DirectoryList
     */
    private $directoryList;

    /**
     * @var WriteFactory
     */
    private $writeFactory;
    
    public function setUp()
    {
        $this->tempDirectory = sprintf('%s/%s/var', realpath(sys_get_temp_dir()), $this->getName());
        @mkdir($this->tempDirectory, 0777, true);
        @mkdir($this->tempDirectory . '/failed', 0777, true);
        @mkdir($this->tempDirectory . '/archived', 0777, true);

        $this->directoryList = new DirectoryList(dirname($this->tempDirectory));
        $this->writeFactory = new WriteFactory(new DriverPool());
    }

    private function getCron(array $data = null) : ArchiveFiles
    {
        if (null === $data) {
            $data = [
                'product' => [
                    'type' => 'files',
                    'archive_old_files' => true,
                    'archived_directory' => 'archived',
                    'failed_directory' => 'failed'
                ],
            ];
        }

        $cache = $this->prophesize(CacheInterface::class);
        $cache->load('cache-id')->willReturn(serialize($data))->shouldBeCalled();

        $reader = $this->prophesize(ReaderInterface::class);

        return new ArchiveFiles(
            new Data($reader->reveal(), $cache->reveal(), 'cache-id'),
            $this->directoryList,
            $this->writeFactory
        );
    }

    public function tearDown()
    {
        (new Filesystem)->remove($this->tempDirectory);
    }
    
    public function testNoZipIsCreatedIfNoFiles()
    {
        $this->getCron()->execute();

        self::assertCount(0, array_diff(scandir($this->tempDirectory . '/failed', SCANDIR_SORT_NONE), ['..', '.']));
        self::assertCount(0, array_diff(scandir($this->tempDirectory . '/archived', SCANDIR_SORT_NONE), ['..', '.']));
    }

    public function testNoZipCreatedIfFilesNotOlderThan3Days()
    {
        touch($this->tempDirectory . '/failed/file1.txt');
        touch($this->tempDirectory . '/archived/file1.txt');

        $builder = new MockBuilder();
        $builder->setNamespace('Jh\Import\Cron')
            ->setName('time')
            ->setFunction(function () {
                return 1497434400; //14-6-2017 10:00:00
            });

        $timeMock = $builder->build();
        $timeMock->enable();

        $builder = new MockBuilder();
        $builder->setNamespace('Jh\Import\Cron')
            ->setName('filectime')
            ->setFunction(function () {
                return 1497348000; //13-6-2017 10:00:00
            });

        $fileCTimeMock = $builder->build();
        $fileCTimeMock->enable();

        $this->getCron()->execute();

        $fileCTimeMock->disable();
        $timeMock->disable();

        self::assertFileExists($this->tempDirectory . '/failed/file1.txt');
        self::assertFileExists($this->tempDirectory . '/archived/file1.txt');

        self::assertCount(1, array_diff(scandir($this->tempDirectory . '/failed', SCANDIR_SORT_NONE), ['..', '.']));
        self::assertCount(1, array_diff(scandir($this->tempDirectory . '/archived', SCANDIR_SORT_NONE), ['..', '.']));
    }

    public function testOldFilesZipped()
    {
        touch($this->tempDirectory . '/failed/file1.txt');
        touch($this->tempDirectory . '/failed/file2.txt');
        touch($this->tempDirectory . '/archived/file1.txt');

        $builder = new MockBuilder();
        $builder->setNamespace('Jh\Import\Cron')
            ->setName('time')
            ->setFunction(function () {
                return 1497434400; //14-6-2017 10:00:00
            });

        $timeMock = $builder->build();
        $timeMock->enable();

        $builder = new MockBuilder();
        $builder->setNamespace('Jh\Import\Cron')
            ->setName('filectime')
            ->setFunction(function () {
                return 1496743200; //6-6-2017 10:00:00
            });

        $fileCTimeMock = $builder->build();
        $fileCTimeMock->enable();

        $this->getCron()->execute();

        $fileCTimeMock->disable();
        $timeMock->disable();

        self::assertFileNotExists($this->tempDirectory . '/failed/file1.txt');
        self::assertFileNotExists($this->tempDirectory . '/failed/file2.txt');
        self::assertFileNotExists($this->tempDirectory . '/archived/file1.txt');

        self::assertFileExists($this->tempDirectory . '/failed/failed-14-06-2017-10-00.zip');
        self::assertFileExists($this->tempDirectory . '/archived/archived-14-06-2017-10-00.zip');

        $zip = new \ZipArchive;
        $zip->open($this->tempDirectory . '/failed/failed-14-06-2017-10-00.zip');

        self::assertEquals(2, $zip->numFiles);
        self::assertNotFalse($zip->locateName('file1.txt'));
        self::assertNotFalse($zip->locateName('file2.txt'));

        $zip->close();

        $zip = new \ZipArchive;
        $zip->open($this->tempDirectory . '/archived/archived-14-06-2017-10-00.zip');

        self::assertEquals(1, $zip->numFiles);
        self::assertNotFalse($zip->locateName('file1.txt'));

        $zip->close();

        self::assertCount(1, array_diff(scandir($this->tempDirectory . '/failed', SCANDIR_SORT_NONE), ['..', '.']));
        self::assertCount(1, array_diff(scandir($this->tempDirectory . '/archived', SCANDIR_SORT_NONE), ['..', '.']));
    }

    public function testOnlyFilesOlderThan3DaysIncludedInZip()
    {
        touch($this->tempDirectory . '/failed/file1.txt');
        touch($this->tempDirectory . '/failed/file2.txt');

        $builder = new MockBuilder();
        $builder->setNamespace('Jh\Import\Cron')
            ->setName('time')
            ->setFunction(function () {
                return 1497434400; //14-6-2017 10:00:00
            });

        $timeMock = $builder->build();
        $timeMock->enable();

        $builder = new MockBuilder();
        $builder->setNamespace('Jh\Import\Cron')
            ->setName('filectime')
            ->setFunction(function ($fileName) {
                switch ($fileName) {
                    case $this->tempDirectory . '/failed/file1.txt':
                        return 1496743200; //6-6-2017 10:00:00
                    case $this->tempDirectory . '/failed/file2.txt':
                        return 1497434400; //14-6-2017 10:00:00
                }
            });

        $fileCTimeMock = $builder->build();
        $fileCTimeMock->enable();

        $this->getCron()->execute();

        $fileCTimeMock->disable();
        $timeMock->disable();

        self::assertFileNotExists($this->tempDirectory . '/failed/file1.txt');
        self::assertFileExists($this->tempDirectory . '/failed/file2.txt');
        self::assertFileExists($this->tempDirectory . '/failed/failed-14-06-2017-10-00.zip');

        $zip = new \ZipArchive;
        $zip->open($this->tempDirectory . '/failed/failed-14-06-2017-10-00.zip');

        self::assertEquals(1, $zip->numFiles);
        self::assertNotFalse($zip->locateName('file1.txt'));

        $zip->close();

        self::assertCount(2, array_diff(scandir($this->tempDirectory . '/failed', SCANDIR_SORT_NONE), ['..', '.']));
        self::assertCount(0, array_diff(scandir($this->tempDirectory . '/archived', SCANDIR_SORT_NONE), ['..', '.']));
    }

    public function testOnlyImportWithArchiveOldFilesFlagAreArchived()
    {
        $config = [
            'product' => [
                'type' => 'files',
                'archive_old_files' => false,
            ],
        ];

        touch($this->tempDirectory . '/failed/file1.txt');
        touch($this->tempDirectory . '/failed/file2.txt');

        $builder = new MockBuilder();
        $builder->setNamespace('Jh\Import\Cron')
            ->setName('filectime')
            ->setFunction(function () {
                return 1496743200; //6-6-2017 10:00:00
            });

        $fileCTimeMock = $builder->build();
        $fileCTimeMock->enable();

        $this->getCron($config)->execute();

        $fileCTimeMock->disable();

        self::assertCount(2, array_diff(scandir($this->tempDirectory . '/failed', SCANDIR_SORT_NONE), ['..', '.']));
        self::assertCount(0, array_diff(scandir($this->tempDirectory . '/archived', SCANDIR_SORT_NONE), ['..', '.']));
    }
}
