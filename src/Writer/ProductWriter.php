<?php

namespace Jh\Import\Writer;

use Jh\Import\Import\Record;
use Jh\Import\AttributeProcessor\AttributeProcessor;
use Jh\Import\Import\Result;
use Jh\Import\Report\ReportItem;
use Jh\Import\Source\Source;
use Magento\Catalog\Api\CategoryLinkManagementInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Gallery\Processor;
use Magento\Catalog\Model\ProductFactory;
use Magento\Catalog\Model\ResourceModel\Product as ProductResource;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Interception\PluginList\PluginList;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Store\Api\Data\WebsiteInterface;
use Magento\Store\Api\WebsiteRepositoryInterface;
use Magento\CatalogInventory\Api\StockConfigurationInterface;
use Magento\CatalogInventory\Api\StockItemRepositoryInterface;
use Magento\CatalogInventory\Model\StockRegistryProvider;

/**
 * @author Aydin Hassan <aydin@wearejh.com>
 */
class ProductWriter implements Writer
{

    /**
     * @var ProductFactory
     */
    private $productFactory;

    /**
     * @var ProductResource
     */
    private $productResource;

    /**
     * @var array
     */
    private $updatedProductsIds = [];

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
     * @var array
     */
    private $websiteIds = [];

    /**
     * @var CategoryLinkManagementInterface
     */
    private $categoryLinkManagement;

    /**
     * @var Processor
     */
    private $productGalleryProcessor;

    /**
     * @var StockRegistryProvider
     */
    private $stockRegistryProvider;

    /**
     * @var StockItemRepositoryInterface
     */
    private $stockItemRepository;

    /**
     * @var StockConfigurationInterface
     */
    private $stockConfiguration;

    public function __construct(
        ProductFactory $productFactory,
        ProductResource $productResource,
        PluginList $pluginList,
        AttributeProcessor $attributeProcessor,
        ConfigBuilder $configBuilder,
        WebsiteRepositoryInterface $websiteRepository,
        CategoryLinkManagementInterface $categoryLinkManagement,
        Processor $productGalleryProcessor,
        StockRegistryProvider $stockRegistryProvider,
        StockItemRepositoryInterface $stockItemRepository,
        StockConfigurationInterface $stockConfiguration
    ) {
        $this->productFactory = $productFactory;
        $this->productResource = $productResource;
        $this->pluginList = $pluginList;
        $this->attributeProcessor = $attributeProcessor;
        $this->configBuilder = $configBuilder;
        $this->stockRegistryProvider = $stockRegistryProvider;
        $this->stockItemRepository = $stockItemRepository;
        $this->stockConfiguration = $stockConfiguration;

        $this->websiteIds = array_map(function (WebsiteInterface $website) {
            return $website->getId();
        }, $websiteRepository->getList());
        $this->categoryLinkManagement = $categoryLinkManagement;
        $this->productGalleryProcessor = $productGalleryProcessor;
    }

    public function write(Record $record, ReportItem $report)
    {
        $sku = $record->getColumnValue('sku', null);

        if ($sku === null) {
            $report->addError('Item is missing SKU value.');
        }

        /** @var Product $product */
        $product = $this->productFactory->create();
        $product->setStatus($record->getColumnValue('status', Status::STATUS_DISABLED));
        $product->setSku($sku);
        $product->setName($record->getColumnValue('name'));
        $product->setData('description', $record->getColumnValue('description', ''));
        $product->setData('short_description', $record->getColumnValue('short_description', ''));
        $product->setPrice($record->getColumnValue('price', 0));
        $product->setAttributeSetId($product->getDefaultAttributeSetId());
        $product->setTypeId($record->getColumnValue('type'));
        $product->setWebsiteIds($this->websiteIds);
        $product->setStoreId(0);
        $product->setVisibility($record->getColumnValue('visibility', Product\Visibility::VISIBILITY_BOTH));
        $product->setData('tax_class_id', $record->getColumnValue('tax_class_id'));

        if ($record->getColumnValue('type') === Configurable::TYPE_CODE) {
            $this->configBuilder->build($record, $product);
        }

        foreach ($record->getColumnValue('attributes', [], 'array') as $attributeCode => $attributeValue) {
            if (null === $attributeValue || '' === $attributeValue) {
                //not a real value - skip it
                continue;
            }

            $this->attributeProcessor->setAttributeValue(
                $product,
                $attributeCode,
                $attributeValue,
                $record,
                $report
            );
        }

        foreach ($record->getColumnValue('images', [], 'array') as $image) {
            try {
                $fileName = $this->productGalleryProcessor->addImage(
                    $product,
                    'import/' . ltrim($image['path'], '/'),
                    $image['attributes'],
                    false
                );

                $this->productGalleryProcessor->updateImage(
                    $product,
                    $fileName,
                    [
                        'label' => $image['label'],
                        'disabled' => false
                    ]
                );
            } catch (LocalizedException $e) {
                $report->addWarning(
                    sprintf(
                        'Image: "import/%s" could not be imported. Error: %s',
                        ltrim($image['path'], '/'),
                        $e->getMessage()
                    )
                );
            }
        }

        try {
            $this->productResource->save($product);
            $this->updatedProductsIds[] = $product->getId();

            if ($record->getColumnValue('type') === Configurable::TYPE_CODE) {
                $stock = $this->stockRegistryProvider->getStockItem(
                    $product->getId(),
                    $this->stockConfiguration->getDefaultScopeId()
                );

                $stock->setProductId($product->getId());
                $stock->setQty(0);
                $stock->setIsInStock(true);
                $this->stockItemRepository->save($stock);
            }

            $this->categoryLinkManagement->assignProductToCategories(
                $product->getSku(),
                $record->getColumnValue('categories', [])
            );
        } catch (\Exception $e) {
            $report->addError(sprintf('Product could not be saved. Error: %s', $e->getMessage()));
        }
    }

    public function prepare(Source $source)
    {
        $this->updatedProductsIds = [];

        //force load of plugins
        $this->pluginList->getNext(Product::class, 'save');

        $r = new \ReflectionProperty(PluginList::class, '_processed');
        $r->setAccessible(true);

        $processed     = $r->getValue($this->pluginList);
        $methodKey     = sprintf('%s_save___self', Product::class);
        $pluginNameKey = array_search('clean_cache', $processed[$methodKey], true);
        unset($processed[$methodKey][$pluginNameKey]);
        $r->setValue($this->pluginList, $processed);
    }

    public function finish(Source $source): Result
    {
        return new Result($this->updatedProductsIds);
    }
}
