<?php

namespace Jh\ImportTest\Type;

use Jh\Import\Config;
use Jh\Import\Import\Importer;
use Jh\Import\Import\ImporterFactory;
use Jh\Import\Source\Source;
use Jh\Import\Specification\ImportSpecification;
use Jh\Import\Type\FileMatcher;
use Jh\Import\Type\Files;
use Jh\Import\Writer\Writer;
use Jh\UnitTestHelpers\ObjectHelper;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem\Directory\WriteFactory;
use Magento\Framework\Filesystem\DriverPool;
use Magento\Framework\ObjectManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

/**
 * @author Aydin Hassan <aydin@wearejh.com>
 */
class FilesTest extends TestCase
{
    use ObjectHelper;

    /**
     * @var string
     */
    private $tempDirectory;

    /**
     * @var array
     */
    private $files = [];

    public function setUp()
    {
        $this->tempDirectory = sprintf('%s/%s/var/import', sys_get_temp_dir(), $this->getName());
        @mkdir($this->tempDirectory, 0777, true);

        foreach (['file1.csv', 'file2.csv', 'file3.csv', 'other.txt'] as $file) {
            $this->files[] = sprintf('%s/%s', $this->tempDirectory, $file);
            touch(sprintf('%s/%s', $this->tempDirectory, $file));
        }
    }

    public function tearDown()
    {
        (new Filesystem)->remove($this->tempDirectory);
    }

    public function testFilesTypeCreatesAndRunsImportForEachMatchesFileInIncomingDirectory()
    {
        $config = new Config('product', [
            'specification'      => 'MySpecification',
            'writer'             => 'MyWriter',
            'source'             => 'MySource',
            'incoming_directory' => 'import',
            'match_files'        => '*',
        ]);

        $importFactory = $this->prophesize(ImporterFactory::class);
        $specification = $this->prophesize(ImportSpecification::class);
        $writer        = $this->prophesize(Writer::class);

        $objectManager = $this->prophesize(ObjectManagerInterface::class);
        $objectManager->get('MySpecification')->willReturn($specification);
        $objectManager->get('MyWriter')->willReturn($writer);

        //file1
        $source1 = $this->prophesize(Source::class);
        $import1 = $this->prophesize(Importer::class);
        $objectManager->create('MySource', ['file' => $this->files[0]])->willReturn($source1);

        $importFactory
            ->create($source1->reveal(), $specification->reveal(), $writer->reveal())
            ->willReturn($import1->reveal());

        $import1->process($config)->shouldBeCalled();

        //file2
        $source2 = $this->prophesize(Source::class);
        $import2 = $this->prophesize(Importer::class);
        $objectManager->create('MySource', ['file' => $this->files[1]])->willReturn($source2);

        $importFactory
            ->create($source2->reveal(), $specification->reveal(), $writer->reveal())
            ->willReturn($import2->reveal());

        $import2->process($config)->shouldBeCalled();

        //file3
        $source3 = $this->prophesize(Source::class);
        $import3 = $this->prophesize(Importer::class);
        $objectManager->create('MySource', ['file' => $this->files[2]])->willReturn($source3);

        $importFactory
            ->create($source3->reveal(), $specification->reveal(), $writer->reveal())
            ->willReturn($import3->reveal());

        $import3->process($config)->shouldBeCalled();

        //file4
        $source4 = $this->prophesize(Source::class);
        $import4 = $this->prophesize(Importer::class);
        $objectManager->create('MySource', ['file' => $this->files[3]])->willReturn($source4);

        $importFactory
            ->create($source4->reveal(), $specification->reveal(), $writer->reveal())
            ->willReturn($import4->reveal());

        $import4->process($config)->shouldBeCalled();

        $directoryList = new DirectoryList(dirname($this->tempDirectory, 2));
        $files = new Files(
            $directoryList,
            new WriteFactory(new DriverPool()),
            $objectManager->reveal(),
            $importFactory->reveal(),
            new FileMatcher
        );
        $files->run($config);
    }
}
