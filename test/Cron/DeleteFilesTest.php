<?php

namespace Jh\ImportTest\Cron;

use Jh\Import\Config\Data;
use Jh\Import\Cron\ArchiveFiles;
use Jh\Import\Cron\DeleteFiles;
use Magento\Framework\Serialize\Serializer\Serialize;
use phpmock\MockBuilder;
use PHPUnit\Framework\TestCase;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem\Directory\WriteFactory;
use Magento\Framework\Filesystem\DriverPool;
use Symfony\Component\Filesystem\Filesystem;
use Magento\Framework\Config\ReaderInterface;
use Magento\Framework\Config\CacheInterface;

class DeleteFilesTest extends TestCase
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
    
    public function setUp(): void
    {
        $this->tempDirectory = sprintf('%s/%s/var', realpath(sys_get_temp_dir()), $this->getName());
        @mkdir($this->tempDirectory, 0777, true);
        @mkdir($this->tempDirectory . '/failed', 0777, true);
        @mkdir($this->tempDirectory . '/archived', 0777, true);

        $this->directoryList = new DirectoryList(dirname($this->tempDirectory));
        $this->writeFactory = new WriteFactory(new DriverPool());
    }

    private function getCron(array $data = null): DeleteFiles
    {
        if (null === $data) {
            $data = [
                'product' => [
                    'type' => 'files',
                    'delete_old_files' => true,
                    'archived_directory' => 'archived',
                    'failed_directory' => 'failed'
                ],
            ];
        }

        $cache = $this->prophesize(CacheInterface::class);
        $cache->load('cache-id')->willReturn(serialize($data))->shouldBeCalled();

        $reader = $this->prophesize(ReaderInterface::class);

        return new DeleteFiles(
            new Data($reader->reveal(), $cache->reveal(), 'cache-id', new Serialize()),
            $this->directoryList,
            $this->writeFactory
        );
    }

    public function tearDown(): void
    {
        (new Filesystem())->remove($this->tempDirectory);
    }
    
//    public function testNoZipIsCreatedIfNoFiles()
//    {
//        $this->getCron()->execute();
//
//        self::assertCount(0, array_diff(scandir($this->tempDirectory . '/failed', SCANDIR_SORT_NONE), ['..', '.']));
//        self::assertCount(0, array_diff(scandir($this->tempDirectory . '/archived', SCANDIR_SORT_NONE), ['..', '.']));
//    }

    public function testNoFilesRemoveIfNonOlderThan3Days()
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

    public function testOldFilesRemoved()
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
                return 1496052610; //29-5-2017 10:00:00
            });

        $fileCTimeMock = $builder->build();
        $fileCTimeMock->enable();

        $this->getCron()->execute();

        $fileCTimeMock->disable();
        $timeMock->disable();

        self::assertFileNotExists($this->tempDirectory . '/failed/file1.txt');
        self::assertFileNotExists($this->tempDirectory . '/failed/file2.txt');
        self::assertFileNotExists($this->tempDirectory . '/archived/file1.txt');

        self::assertCount(0, array_diff(scandir($this->tempDirectory . '/failed', SCANDIR_SORT_NONE), ['..', '.']));
        self::assertCount(0, array_diff(scandir($this->tempDirectory . '/archived', SCANDIR_SORT_NONE), ['..', '.']));
    }

    public function testOnlyFilesOlderThan2WeeksDeleted()
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
                        return 1496052610; //29-5-2017 10:00:00
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

        self::assertCount(1, array_diff(scandir($this->tempDirectory . '/failed', SCANDIR_SORT_NONE), ['..', '.']));
        self::assertCount(0, array_diff(scandir($this->tempDirectory . '/archived', SCANDIR_SORT_NONE), ['..', '.']));
    }

    public function testOnlyImportWithDeleteOldFilesFlagAreDeleted()
    {
        $config = [
            'product' => [
                'type' => 'files',
                'delete_old_files' => false,
            ],
        ];

        touch($this->tempDirectory . '/failed/file1.txt');
        touch($this->tempDirectory . '/failed/file2.txt');

        $builder = new MockBuilder();
        $builder->setNamespace('Jh\Import\Cron')
            ->setName('filectime')
            ->setFunction(function () {
                return 1496052610; //29-5-2017 10:00:00
            });

        $fileCTimeMock = $builder->build();
        $fileCTimeMock->enable();

        $this->getCron($config)->execute();

        $fileCTimeMock->disable();

        self::assertCount(2, array_diff(scandir($this->tempDirectory . '/failed', SCANDIR_SORT_NONE), ['..', '.']));
        self::assertCount(0, array_diff(scandir($this->tempDirectory . '/archived', SCANDIR_SORT_NONE), ['..', '.']));
    }
}
