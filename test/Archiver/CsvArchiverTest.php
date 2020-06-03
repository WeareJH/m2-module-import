<?php

namespace Jh\ImportTest\Archiver;

use Jh\Import\Archiver\CsvArchiver;
use Jh\Import\Config;
use Jh\Import\Source\Csv;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem\Driver\File;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

/**
 * @author Aydin Hassan <aydin@wearejh.com>
 */
class CsvArchiverTest extends TestCase
{
    /**
     * @var string
     */
    private $tempRoot;

    /**
     * @var string
     */
    private $testFileLocation;

    /**
     * @var DirectoryList
     */
    private $directoryList;

    /**
     * @var Csv
     */
    private $source;

    /**
     * @var CsvArchiver
     */
    private $archiver;

    /**
     * @var \DateTime
     */
    private $date;

    public function setUp() : void
    {
        $this->tempRoot = sprintf('%s/%s', sys_get_temp_dir(), $this->getName());
        @mkdir($this->tempRoot, 0777, true);
        $this->testFileLocation = sprintf('%s/my-file.csv', $this->tempRoot);
        touch($this->testFileLocation);

        $this->directoryList = new DirectoryList($this->tempRoot);
        $this->source = $this->prophesize(Csv::class);

        $this->source->getFile()->willReturn(new \SplFileObject($this->testFileLocation, 'r'));

        $this->date = new \DateTime('02-03-2017 10:15:00');
        $this->archiver = new CsvArchiver(
            $this->source->reveal(),
            new Config('product', [
                'archived_directory' => 'jh_import/archived',
                'failed_directory'   => 'jh_import/failed',
            ]),
            $this->directoryList,
            new File,
            $this->date
        );
    }

    public function tearDown() : void
    {
        (new Filesystem)->remove($this->tempRoot);
    }

    public function testFailedMovesToFailedFolderAndRenamesFileWithCurrentDate()
    {
        mkdir($this->directoryList->getPath(DirectoryList::VAR_DIR) . '/jh_import/failed', 0777, true);

        $this->archiver->failed();

        self::assertFileExists(sprintf('%s/var/jh_import/failed/my-file-02032017101500.csv', $this->tempRoot));
        self::assertFileNotExists($this->testFileLocation);
    }

    public function testFailedMovesToFailedFolderAndCreatesFolderWhenItDoesNotExist()
    {
        $this->archiver->failed();

        self::assertFileExists(sprintf('%s/var/jh_import/failed/my-file-02032017101500.csv', $this->tempRoot));
        self::assertFileNotExists($this->testFileLocation);
    }

    public function testSuccessMovesToArchivedFolderAndRenamesFileWithCurrentDate()
    {
        mkdir($this->directoryList->getPath(DirectoryList::VAR_DIR) . '/jh_import/archived', 0777, true);

        $this->archiver->successful();

        self::assertFileExists(sprintf('%s/var/jh_import/archived/my-file-02032017101500.csv', $this->tempRoot));
        self::assertFileNotExists($this->testFileLocation);
    }

    public function testSuccessMovesToArchivedFolderAndCreatesFolderWhenItDoesNotExist()
    {
        $this->archiver->successful();

        self::assertFileExists(sprintf('%s/var/jh_import/archived/my-file-02032017101500.csv', $this->tempRoot));
        self::assertFileNotExists($this->testFileLocation);
    }
}
