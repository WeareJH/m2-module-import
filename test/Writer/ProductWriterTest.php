<?php

namespace Jh\ImportTest\Writer;

use Jh\Import\Import\Record;
use Jh\Import\AttributeProcessor\AttributeProcessor;
use Jh\Import\Report\CollectingReport;
use Jh\Import\Report\ReportItem;
use Jh\Import\Source\Source;
use Jh\Import\Writer\ConfigBuilder;
use Jh\Import\Writer\ProductWriter;
use Magento\Catalog\Api\CategoryLinkManagementInterface;
use Magento\Catalog\Model\CategoryLinkManagement;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Gallery\Processor;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\ProductFactory;
use Magento\Catalog\Model\ResourceModel\Product as ProductResource;
use Magento\CatalogInventory\Api\Data\StockItemInterface;
use Magento\CatalogInventory\Model\StockRegistryProvider;
use Magento\CatalogInventory\Api\StockItemRepositoryInterface;
use Magento\CatalogInventory\Api\StockConfigurationInterface;
use Magento\Framework\Interception\PluginList\PluginList;
use Magento\Store\Api\Data\WebsiteInterface;
use Magento\Store\Api\WebsiteRepositoryInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;

/**
 * @author Aydin Hassan <aydin@wearejh.com>
 */
class ProductWriterTest extends TestCase
{
    /**
     * @var  ProductFactory
     */
    private $productFactory;

    /**
     * @var ProductResource
     */
    private $productResource;

    /**
     * @var PluginList
     */
    private $pluginList;

    /**
     * @var AttributeProcessor
     */
    private $attributeProcessor;

    /**
     * @var ConfigBuilder
     */
    private $configBuilder;

    /**
     * @var WebsiteRepositoryInterface
     */
    private $websiteRepo;

    /**
     * @var CategoryLinkManagement
     */
    private $categoryLink;

    /**
     * @var Processor
     */
    private $imageProcessor;

    /**
     * @var ProductWriter
     */
    private $productWriter;

    /**
     * @var StockRegistryProvider
     */
    private $stockRegistryProvider;

    /**
     * @var StockItemRepositoryInterface
     */
    private $stockItemRepositoryInterface;

    /**
     * @var StockConfigurationInterface
     */
    private $stockConfigurationInterface;

    /**
     * @var StockItemInterface
     */
    private $stock;

    public function setUp(): void
    {
        $this->productFactory     = $this->prophesize(ProductFactory::class);
        $this->productResource    = $this->prophesize(ProductResource::class);
        $this->pluginList         = $this->prophesize(PluginList::class);
        $this->attributeProcessor = $this->prophesize(AttributeProcessor::class);
        $this->configBuilder      = $this->prophesize(ConfigBuilder::class);
        $this->websiteRepo        = $this->prophesize(WebsiteRepositoryInterface::class);
        $this->categoryLink       = $this->prophesize(CategoryLinkManagementInterface::class);
        $this->imageProcessor     = $this->prophesize(Processor::class);
        $this->stock              = $this->prophesize(StockItemInterface::class);
        $this->stockRegistryProvider        = $this->prophesize(StockRegistryProvider::class);
        $this->stockItemRepositoryInterface = $this->prophesize(StockItemRepositoryInterface::class);
        $this->stockConfigurationInterface  = $this->prophesize(StockConfigurationInterface::class);


        $website = $this->prophesize(WebsiteInterface::class);
        $website->getId()->willReturn(2);
        $this->websiteRepo->getList()->willReturn([$website->reveal()]);

        $this->productWriter = new ProductWriter(
            $this->productFactory->reveal(),
            $this->productResource->reveal(),
            $this->pluginList->reveal(),
            $this->attributeProcessor->reveal(),
            $this->configBuilder->reveal(),
            $this->websiteRepo->reveal(),
            $this->categoryLink->reveal(),
            $this->imageProcessor->reveal(),
            $this->stockRegistryProvider->reveal(),
            $this->stockItemRepositoryInterface->reveal(),
            $this->stockConfigurationInterface->reveal()
        );
    }

    public function testSimpleProductIsSaved()
    {
        $product = $this->prophesize(Product::class);
        $this->productFactory->create()->willReturn($product->reveal());

        $record = new Record(10, [
            'status'            => 1,
            'sku'               => 'PROD1',
            'name'              => 'Product 1',
            'description'       => 'Product Description',
            'short_description' => 'Product Short Description',
            'price'             => 100,
            'type'              => 'simple',
            'visibility'        => Visibility::VISIBILITY_BOTH,
            'tax_class_id'      => 5
        ]);

        $this->productWriter->write(
            $record,
            new ReportItem([], 100, 'sku', 100)
        );

        $product->setStatus(1)->shouldHaveBeenCalled();
        $product->setSku('PROD1')->shouldHaveBeenCalled();
        $product->setName('Product 1')->shouldHaveBeenCalled();
        $product->setData('description', 'Product Description')->shouldHaveBeenCalled();
        $product->setData('short_description', 'Product Short Description')->shouldHaveBeenCalled();
        $product->setPrice(100)->shouldHaveBeenCalled();
        $product->setAttributeSetId(null)->shouldHaveBeenCalled();
        $product->setTypeId('simple')->shouldHaveBeenCalled();
        $product->setWebsiteIds([2])->shouldHaveBeenCalled();
        $product->setStoreId(0)->shouldHaveBeenCalled();
        $product->setVisibility(Visibility::VISIBILITY_BOTH)->shouldHaveBeenCalled();
        $product->setData('tax_class_id', 5)->shouldHaveBeenCalled();

        $this->productResource->save($product->reveal())->shouldHaveBeenCalled();
    }

    public function testConfigProductIsPassedToConfigBuilder()
    {
        $product = $this->prophesize(Product::class);
        $this->productFactory->create()->willReturn($product->reveal());
        $this->stockRegistryProvider->getStockItem(null, null)->willReturn($this->stock->reveal());

        $record = new Record(10, [
            'status'            => 1,
            'sku'               => 'PROD1',
            'name'              => 'Product 1',
            'description'       => 'Product Description',
            'short_description' => 'Product Short Description',
            'price'             => 100,
            'type'              => 'configurable',
            'visibility'        => Visibility::VISIBILITY_BOTH,
            'tax_class_id'      => 5
        ]);

        $this->productWriter->write(
            $record,
            new ReportItem([], 100, 'sku', 100)
        );

        $product->setStatus(1)->shouldHaveBeenCalled();
        $product->setSku('PROD1')->shouldHaveBeenCalled();
        $product->setName('Product 1')->shouldHaveBeenCalled();
        $product->setData('description', 'Product Description')->shouldHaveBeenCalled();
        $product->setData('short_description', 'Product Short Description')->shouldHaveBeenCalled();
        $product->setPrice(100)->shouldHaveBeenCalled();
        $product->setAttributeSetId(null)->shouldHaveBeenCalled();
        $product->setTypeId('configurable')->shouldHaveBeenCalled();
        $product->setWebsiteIds([2])->shouldHaveBeenCalled();
        $product->setStoreId(0)->shouldHaveBeenCalled();
        $product->setVisibility(Visibility::VISIBILITY_BOTH)->shouldHaveBeenCalled();
        $product->setData('tax_class_id', 5)->shouldHaveBeenCalled();

        $this->stock->setProductId(null)->shouldHaveBeenCalled();

        $this->configBuilder->build($record, $product->reveal())->shouldHaveBeenCalled();
        $this->productResource->save($product->reveal())->shouldHaveBeenCalled();
    }

    public function testSimpleProductIsSavedAndExtraAttributesArePassedToAttributeProcessor()
    {
        $product = $this->prophesize(Product::class);
        $this->productFactory->create()->willReturn($product->reveal());

        $record = new Record(10, [
            'status'            => 1,
            'sku'               => 'PROD1',
            'name'              => 'Product 1',
            'description'       => 'Product Description',
            'short_description' => 'Product Short Description',
            'price'             => 100,
            'type'              => 'simple',
            'visibility'        => Visibility::VISIBILITY_BOTH,
            'tax_class_id'      => 5,
            'attributes'        => [
                'size'   => 10,
                'colour' => 'blue'
            ]
        ]);

        $this->productWriter->write(
            $record,
            $report = new ReportItem([], 100, 'sku', 100)
        );

        $product->setStatus(1)->shouldHaveBeenCalled();
        $product->setSku('PROD1')->shouldHaveBeenCalled();
        $product->setName('Product 1')->shouldHaveBeenCalled();
        $product->setData('description', 'Product Description')->shouldHaveBeenCalled();
        $product->setData('short_description', 'Product Short Description')->shouldHaveBeenCalled();
        $product->setPrice(100)->shouldHaveBeenCalled();
        $product->setAttributeSetId(null)->shouldHaveBeenCalled();
        $product->setTypeId('simple')->shouldHaveBeenCalled();
        $product->setWebsiteIds([2])->shouldHaveBeenCalled();
        $product->setStoreId(0)->shouldHaveBeenCalled();
        $product->setVisibility(Visibility::VISIBILITY_BOTH)->shouldHaveBeenCalled();
        $product->setData('tax_class_id', 5)->shouldHaveBeenCalled();

        $this->attributeProcessor->setAttributeValue($product->reveal(), 'size', 10, $record, $report)
            ->shouldHaveBeenCalled();

        $this->attributeProcessor->setAttributeValue($product->reveal(), 'colour', 'blue', $record, $report)
            ->shouldHaveBeenCalled();

        $this->productResource->save($product->reveal())->shouldHaveBeenCalled();
    }

    public function testProductWithImagesAreAdded()
    {
        $product = $this->prophesize(Product::class);
        $this->productFactory->create()->willReturn($product->reveal());

        $record = new Record(10, [
            'status'            => 1,
            'sku'               => 'PROD1',
            'name'              => 'Product 1',
            'description'       => 'Product Description',
            'short_description' => 'Product Short Description',
            'price'             => 100,
            'type'              => 'simple',
            'visibility'        => Visibility::VISIBILITY_BOTH,
            'tax_class_id'      => 5,
            'images'            => [
                [
                    'path'       => 'var/images/my-image.png',
                    'label'      => 'My Image',
                    'attributes' => []
                ]
            ]
        ]);

        $this->imageProcessor->addImage($product->reveal(), 'import/var/images/my-image.png', [], false)
            ->willReturn('my-file')
            ->shouldBeCalled();

        $this->imageProcessor->updateImage($product->reveal(), 'my-file', ['label' => 'My Image', 'disabled' => false])
            ->shouldBeCalled();

        $this->productWriter->write(
            $record,
            new ReportItem([], 100, 'sku', 100)
        );

        $product->setStatus(1)->shouldHaveBeenCalled();
        $product->setSku('PROD1')->shouldHaveBeenCalled();
        $product->setName('Product 1')->shouldHaveBeenCalled();
        $product->setData('description', 'Product Description')->shouldHaveBeenCalled();
        $product->setData('short_description', 'Product Short Description')->shouldHaveBeenCalled();
        $product->setPrice(100)->shouldHaveBeenCalled();
        $product->setAttributeSetId(null)->shouldHaveBeenCalled();
        $product->setTypeId('simple')->shouldHaveBeenCalled();
        $product->setWebsiteIds([2])->shouldHaveBeenCalled();
        $product->setStoreId(0)->shouldHaveBeenCalled();
        $product->setVisibility(Visibility::VISIBILITY_BOTH)->shouldHaveBeenCalled();
        $product->setData('tax_class_id', 5)->shouldHaveBeenCalled();

        $this->productResource->save($product->reveal())->shouldHaveBeenCalled();
    }

    public function testProductWithCategories()
    {
        $product = $this->prophesize(Product::class);
        $this->productFactory->create()->willReturn($product->reveal());

        $record = new Record(10, [
            'status'            => 1,
            'sku'               => 'PROD1',
            'name'              => 'Product 1',
            'description'       => 'Product Description',
            'short_description' => 'Product Short Description',
            'price'             => 100,
            'type'              => 'simple',
            'visibility'        => Visibility::VISIBILITY_BOTH,
            'tax_class_id'      => 5,
            'categories'        => [1, 5]
        ]);

        $this->productWriter->write(
            $record,
            new ReportItem([], 100, 'sku', 100)
        );

        $product->setStatus(1)->shouldHaveBeenCalled();
        $product->setSku('PROD1')->shouldHaveBeenCalled();
        $product->setName('Product 1')->shouldHaveBeenCalled();
        $product->setData('description', 'Product Description')->shouldHaveBeenCalled();
        $product->setData('short_description', 'Product Short Description')->shouldHaveBeenCalled();
        $product->setPrice(100)->shouldHaveBeenCalled();
        $product->setAttributeSetId(null)->shouldHaveBeenCalled();
        $product->setTypeId('simple')->shouldHaveBeenCalled();
        $product->setWebsiteIds([2])->shouldHaveBeenCalled();
        $product->setStoreId(0)->shouldHaveBeenCalled();
        $product->setVisibility(Visibility::VISIBILITY_BOTH)->shouldHaveBeenCalled();
        $product->setData('tax_class_id', 5)->shouldHaveBeenCalled();

        $this->productResource->save($product->reveal())->shouldHaveBeenCalled();
        $this->categoryLink->assignProductToCategories('PROD1', [1, 5]);
    }

    public function testFinishReturnsIdsOfCreatedProducts()
    {
        $product1 = $this->prophesize(Product::class);
        $product2 = $this->prophesize(Product::class);
        $product3 = $this->prophesize(Product::class);
        $this->productFactory->create()->willReturn($product1, $product2, $product3);

        $record1 = $this->setupProductExpectationAndCreateRecord($product1, 10, 'PROD1', 'Product 1');
        $record2 = $this->setupProductExpectationAndCreateRecord($product2, 11, 'PROD2', 'Product 2');
        $record3 = $this->setupProductExpectationAndCreateRecord($product3, 12, 'PROD3', 'Product 3');

        $this->productResource->save($product1->reveal())->shouldBeCalled();
        $product1->getId()->willReturn(56);

        $this->productWriter->write(
            $record1,
            new ReportItem([], 100, 'sku', 100)
        );

        $this->productResource->save($product2->reveal())->shouldBeCalled();
        $product2->getId()->willReturn(57);

        $this->productWriter->write(
            $record2,
            new ReportItem([], 101, 'sku', 101)
        );

        $this->productResource->save($product3->reveal())->shouldBeCalled();
        $product3->getId()->willReturn(58);

        $this->productWriter->write(
            $record3,
            new ReportItem([], 102, 'sku', 102)
        );

        $result = $this->productWriter->finish($this->prophesize(Source::class)->reveal());

        self::assertSame([56, 57, 58], $result->getAffectedIds());
    }

    private function setupProductExpectationAndCreateRecord(ObjectProphecy $product, int $id, string $sku, string $name)
    {
        $product->setStatus(1)->shouldBeCalled();
        $product->setSku($sku)->shouldBeCalled();
        $product->setName($name)->shouldBeCalled();
        $product->setData('description', 'Product Description')->shouldBeCalled();
        $product->setData('short_description', 'Product Short Description')->shouldBeCalled();
        $product->setPrice(100)->shouldBeCalled();
        $product->getDefaultAttributeSetId()->willReturn(5);
        $product->setAttributeSetId(5)->shouldBeCalled();
        $product->setTypeId('simple')->shouldBeCalled();
        $product->setWebsiteIds([2])->shouldBeCalled();
        $product->setStoreId(0)->shouldBeCalled();
        $product->setVisibility(Visibility::VISIBILITY_BOTH)->shouldBeCalled();
        $product->setData('tax_class_id', 5)->shouldBeCalled();
        $product->getSku()->shouldBeCalled();

        return new Record($id, [
            'status'            => 1,
            'sku'               => $sku,
            'name'              => $name,
            'description'       => 'Product Description',
            'short_description' => 'Product Short Description',
            'price'             => 100,
            'type'              => 'simple',
            'visibility'        => Visibility::VISIBILITY_BOTH,
            'tax_class_id'      => 5
        ]);
    }
}
