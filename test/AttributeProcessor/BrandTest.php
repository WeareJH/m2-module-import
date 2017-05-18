<?php

namespace Jh\ImportTest\Archiver;

use Symfony\Component\Filesystem\Filesystem as SymfonyFilesystem;
use Jh\Import\AttributeProcessor\Brand as BrandProcessor;
use Jh\Import\Import\Record;
use Jh\Import\Report\ReportItem;
use Jh\Brands\Model\Brand;
use Jh\Brands\Model\BrandFactory;
use Jh\Brands\Model\BrandRepository;
use Jh\Brands\Model\BrandSearchResult;
use Magento\Eav\Api\Data\AttributeInterface;
use Magento\Framework\Api\Search\SearchCriteria;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Directory\ReadFactory;
use Magento\Framework\Filesystem\Directory\WriteFactory;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\Filesystem\DriverPool;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;

/**
 * @author Aydin Hassan <aydin@wearejh.com>
 */
class BrandTest extends TestCase
{
    private $brandFactory;
    private $brandRepository;

    /**
     * @var BrandProcessor
     */
    private $brandProcessor;
    private $tempDirectory;

    public function setUp()
    {
        if (!class_exists(Brand::class)) {
            $this->markTestSkipped('wearejh/m2-module-brands not installed');
        }

        $this->tempDirectory = sprintf('%s/%s', sys_get_temp_dir(), $this->getName());
        @mkdir($this->tempDirectory, 0777, true);
        $this->brandFactory = $this->prophesize(BrandFactory::class);
        $this->brandRepository = $this->prophesize(BrandRepository::class);
        $this->brandProcessor = new BrandProcessor(
            $this->brandFactory->reveal(),
            $this->brandRepository->reveal(),
            new File,
            new Filesystem(
                new DirectoryList($this->tempDirectory),
                new ReadFactory(new DriverPool),
                new WriteFactory(new DriverPool)
            )
        );
    }

    public function tearDown()
    {
        (new SymfonyFilesystem)->remove($this->tempDirectory);
    }

    public function testIfBrandExistsNoNewBrandIsCreated()
    {
        $brand = $this->prophesize(Brand::class);
        $brand->getName()->willReturn('Atom');
        $brand->getId()->willReturn(12);

        $searchResult = new BrandSearchResult;
        $searchResult->setItems([$brand->reveal()]);
        $this->brandRepository->getList(Argument::type(SearchCriteria::class))->willReturn($searchResult);

        $attribute = $this->prophesize(AttributeInterface::class);

        $id = $this->brandProcessor->process(
            $attribute->reveal(),
            'Atom',
            new Record(10),
            $this->prophesize(ReportItem::class)->reveal()
        );

        self::assertSame(12, $id);
        $this->brandRepository->save()->shouldNotHaveBeenCalled();
    }

    public function testBrandIsCreatedIfItDoesNotExist()
    {
        $brand = $this->prophesize(Brand::class);
        $brand->getName()->willReturn('Atom');
        $brand->getId()->willReturn(12);

        $searchResult = new BrandSearchResult;
        $searchResult->setItems([$brand->reveal()]);
        $this->brandRepository->getList(Argument::type(SearchCriteria::class))->willReturn($searchResult);

        $newBrand = $this->prophesize(Brand::class);
        $newBrand->getId()->willReturn(4);
        $newBrand->setData(['name' => 'Some Brand', 'description' => 'Some Brand'])
            ->willReturn($newBrand->reveal())
            ->shouldBeCalled();

        $this->brandRepository->save($newBrand->reveal())->willReturn($newBrand->reveal())->shouldBeCalled();
        $this->brandFactory->create()->willReturn($newBrand->reveal());

        $attribute = $this->prophesize(AttributeInterface::class);

        $id = $this->brandProcessor->process(
            $attribute->reveal(),
            'Some Brand',
            new Record(10),
            $this->prophesize(ReportItem::class)->reveal()
        );

        self::assertSame(4, $id);
    }

    public function testBrandIsOnlyCreatedOnceIfItDoesNotExist()
    {
        $brand = $this->prophesize(Brand::class);
        $brand->getName()->willReturn('Atom');
        $brand->getId()->willReturn(12);

        $searchResult = new BrandSearchResult;
        $searchResult->setItems([$brand->reveal()]);
        $this->brandRepository->getList(Argument::type(SearchCriteria::class))->willReturn($searchResult);

        $newBrand = $this->prophesize(Brand::class);
        $newBrand->getId()->willReturn(4);
        $newBrand->setData(['name' => 'Some Brand', 'description' => 'Some Brand'])
            ->willReturn($newBrand->reveal())
            ->shouldBeCalledTimes(1);

        $this->brandRepository->save($newBrand->reveal())->willReturn($newBrand->reveal())->shouldBeCalledTimes(1);
        $this->brandFactory->create()->willReturn($newBrand->reveal())->shouldBeCalledTimes(1);

        $attribute = $this->prophesize(AttributeInterface::class);

        $id = $this->brandProcessor->process(
            $attribute->reveal(),
            'Some Brand',
            new Record(10),
            $this->prophesize(ReportItem::class)->reveal()
        );

        self::assertSame(4, $id);

        $id = $this->brandProcessor->process(
            $attribute->reveal(),
            'Some Brand',
            new Record(10),
            $this->prophesize(ReportItem::class)->reveal()
        );

        self::assertSame(4, $id);
    }

    public function testBrandIsSavedWithoutImageIfImageDoesNotExist()
    {
        $searchResult = new BrandSearchResult;
        $searchResult->setItems([]);
        $this->brandRepository->getList(Argument::type(SearchCriteria::class))->willReturn($searchResult);

        $newBrand = $this->prophesize(Brand::class);
        $newBrand->getId()->willReturn(4);
        $newBrand->setData(['name' => 'Some Brand', 'description' => 'Some Brand'])
            ->willReturn($newBrand->reveal())
            ->shouldBeCalled();

        $this->brandRepository->save($newBrand->reveal())->willReturn($newBrand->reveal())->shouldBeCalled();
        $this->brandFactory->create()->willReturn($newBrand->reveal());

        $attribute = $this->prophesize(AttributeInterface::class);
        $report = $this->prophesize(ReportItem::class);
        $this->brandProcessor->process(
            $attribute->reveal(),
            'Some Brand',
            new Record(10, ['brand_image' => '/some/file/that/does/not/exist']),
            $report->reveal()
        );

        $msg  = 'Could not import image for brand: "Some Brand". Error: ';
        $msg .= 'Image: "/some/file/that/does/not/exist" does not exist';
        $report->addWarning($msg)->shouldHaveBeenCalled();
    }

    public function testBrandIsSavedWithoutImageIfImageWithSameNameAlreadyExists()
    {
        touch(sprintf('%s/my-image.png', $this->tempDirectory));
        mkdir(sprintf('%s/pub/media/catalog/product/brands', $this->tempDirectory), 0777, true);
        touch(sprintf('%s/pub/media/catalog/product/brands/my-image.png', $this->tempDirectory));

        $searchResult = new BrandSearchResult;
        $searchResult->setItems([]);
        $this->brandRepository->getList(Argument::type(SearchCriteria::class))->willReturn($searchResult);

        $newBrand = $this->prophesize(Brand::class);
        $newBrand->getId()->willReturn(4);
        $newBrand->setData(['name' => 'Some Brand', 'description' => 'Some Brand'])
            ->willReturn($newBrand->reveal())
            ->shouldBeCalled();

        $this->brandRepository->save($newBrand->reveal())->willReturn($newBrand->reveal())->shouldBeCalled();
        $this->brandFactory->create()->willReturn($newBrand->reveal());

        $attribute = $this->prophesize(AttributeInterface::class);
        $report = $this->prophesize(ReportItem::class);
        $this->brandProcessor->process(
            $attribute->reveal(),
            'Some Brand',
            new Record(10, ['brand_image' => sprintf('%s/my-image.png', $this->tempDirectory)]),
            $report->reveal()
        );

        $msg  = 'Could not import image for brand: "Some Brand". Error: ';
        $msg .= 'Image with the name: "my-image.png" already exists in the pub folder';
        $report->addWarning($msg)->shouldHaveBeenCalled();
    }

    public function testImageIsImportedWithBrandIfOneIsSpecified()
    {
        touch(sprintf('%s/my-image.png', $this->tempDirectory));

        $brand = $this->prophesize(Brand::class);
        $brand->getName()->willReturn('Atom');
        $brand->getId()->willReturn(12);

        $searchResult = new BrandSearchResult;
        $searchResult->setItems([$brand->reveal()]);
        $this->brandRepository->getList(Argument::type(SearchCriteria::class))->willReturn($searchResult);

        $newBrand = $this->prophesize(Brand::class);
        $newBrand->getId()->willReturn(4);
        $newBrand->setData([
            'name' => 'Some Brand',
            'description' => 'Some Brand',
            'logo' => 'catalog/product/brands/my-image.png'
            ])
            ->willReturn($newBrand->reveal())
            ->shouldBeCalled();

        $this->brandRepository->save($newBrand->reveal())->willReturn($newBrand->reveal())->shouldBeCalled();
        $this->brandFactory->create()->willReturn($newBrand->reveal());

        $attribute = $this->prophesize(AttributeInterface::class);
        $report = $this->prophesize(ReportItem::class);
        $id = $this->brandProcessor->process(
            $attribute->reveal(),
            'Some Brand',
            new Record(10, ['brand_image' => sprintf('%s/my-image.png', $this->tempDirectory)]),
            $report->reveal()
        );

        self::assertSame(4, $id);
        self::assertFileExists(sprintf('%s/pub/media/catalog/product/brands/my-image.png', $this->tempDirectory));
        self::assertFileNotExists(sprintf('%s/my-image.png', $this->tempDirectory));
    }
}
