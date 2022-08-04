<?php

namespace Jh\ImportTest\Block;

use Jh\Import\Block\Info;
use Jh\Import\Block\TypeFiles;
use Jh\Import\Config;
use Jh\Import\Type\FileMatcher;
use Jh\UnitTestHelpers\ObjectHelper;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem\Directory\WriteFactory;
use Magento\Framework\Filesystem\DriverPool;
use Magento\Framework\View\LayoutInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;

class TypeFilesTest extends TestCase
{
    use ObjectHelper;
    use ProphecyTrait;

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

    /**
     * @var TypeFiles
     */
    private $block;

    public function setUp(): void
    {
        $this->tempDirectory = sprintf('%s/%s/var', realpath(sys_get_temp_dir()), $this->getName());
        @mkdir($this->tempDirectory, 0777, true);

        $this->directoryList = new DirectoryList(dirname($this->tempDirectory));
        $this->writeFactory = new WriteFactory(new DriverPool());

        $import = new Config('product', [
            'incoming_directory' => 'jh_import/incoming',
            'failed_directory' => 'jh_import/failed',
            'archived_directory' => 'jh_import/archived',
            'match_files' => 'file1.txt'
        ]);

        $parent = $this->prophesize(Info::class);
        $parent->getImport()->willReturn($import);

        $layout = $this->prophesize(LayoutInterface::class);
        $layout->getParentName('type-info')->willReturn('info');
        $layout->getBlock('info')->willReturn($parent);

        $context = $this->getObject(Context::class, [
            'layout' => $layout->reveal()
        ]);

        $this->block = new TypeFiles(
            $context,
            $this->directoryList,
            $this->writeFactory,
            new FileMatcher()
        );
        $this->block->setNameInLayout('type-info');
    }

    public function testGetDirectories()
    {
        self::assertEquals(
            $this->tempDirectory . '/jh_import/incoming',
            $this->block->incomingDirectory()
        );

        self::assertEquals(
            $this->tempDirectory . '/jh_import/failed',
            $this->block->failedDirectory()
        );

        self::assertEquals(
            $this->tempDirectory . '/jh_import/archived',
            $this->block->archivedDirectory()
        );
    }

    public function testGetDownloadUrl()
    {
        $this->retrieveChildMock(Context::class, 'urlBuilder')
            ->getUrl('jh_import/files/download', [
                'name'       => 'product',
                'directory'  => 'import',
                'file'       => 'some+file.csv'
            ])
            ->willReturn('http://some-site.com/download/some+file.csv');

        self::assertEquals(
            'http://some-site.com/download/some+file.csv',
            $this->block->getDownloadUrl(new \SplFileInfo('some file.csv'), 'import')
        );
    }

    public function testGetImport()
    {
        self::assertInstanceOf(Config::class, $this->block->getImport());
    }

    public function testFilesNew()
    {
        @mkdir($this->tempDirectory . '/jh_import/incoming', 0777, true);
        touch($this->tempDirectory . '/jh_import/incoming/file1.txt');
        touch($this->tempDirectory . '/jh_import/incoming/file2.txt');

        $filesNew = $this->block->filesNew();

        self::assertContainsOnly(\SplFileInfo::class, $filesNew);

        $filesNew = array_map(function (\SplFileInfo $fileInfo) {
            return $fileInfo->getRealPath();
        }, $filesNew);

        self::assertEquals(
            [
                $this->tempDirectory . '/jh_import/incoming/file1.txt',
                $this->tempDirectory . '/jh_import/incoming/file2.txt',
            ],
            $filesNew
        );
    }

    public function testFilesFailed()
    {
        @mkdir($this->tempDirectory . '/jh_import/failed', 0777, true);
        touch($this->tempDirectory . '/jh_import/failed/file1.txt');
        touch($this->tempDirectory . '/jh_import/failed/file2.txt');

        $filesNew = $this->block->filesFailed();

        self::assertContainsOnly(\SplFileInfo::class, $filesNew);

        $filesNew = array_map(function (\SplFileInfo $fileInfo) {
            return $fileInfo->getRealPath();
        }, $filesNew);

        self::assertEquals(
            [
                $this->tempDirectory . '/jh_import/failed/file1.txt',
                $this->tempDirectory . '/jh_import/failed/file2.txt',
            ],
            $filesNew
        );
    }

    public function testFilesArchived()
    {
        @mkdir($this->tempDirectory . '/jh_import/archived', 0777, true);
        touch($this->tempDirectory . '/jh_import/archived/file1.txt');
        touch($this->tempDirectory . '/jh_import/archived/file2.txt');

        $filesNew = $this->block->filesArchived();

        self::assertContainsOnly(\SplFileInfo::class, $filesNew);

        $filesNew = array_map(function (\SplFileInfo $fileInfo) {
            return $fileInfo->getRealPath();
        }, $filesNew);

        self::assertEquals(
            [
                $this->tempDirectory . '/jh_import/archived/file1.txt',
                $this->tempDirectory . '/jh_import/archived/file2.txt',
            ],
            $filesNew
        );
    }

    public function testFileWillBeProcessed()
    {
        self::assertTrue($this->block->fileWillBeProcessed(new \SplFileInfo('file1.txt')));
        self::assertFalse($this->block->fileWillBeProcessed(new \SplFileInfo('file2.txt')));
    }
}
