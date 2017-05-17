<?php

namespace Jh\ImportTest\Asset;

use Magento\Catalog\Api\Data\ProductExtensionInterface;
use Magento\Framework\Api\AbstractSimpleObject;

/**
 * THIS CLASS IS COPIED FROM var/generation for usage in unit tests
 *
 * @author Aydin Hassan <aydin@wearejh.com>
 * Extension class for @see \Magento\Catalog\Api\Data\ProductInterface
 */
class ProductExtension extends AbstractSimpleObject implements ProductExtensionInterface
{
    /**
     * @return \Magento\Bundle\Api\Data\OptionInterface[]|null
     */
    public function getBundleProductOptions()
    {
        return $this->_get('bundle_product_options');
    }

    /**
     * @param \Magento\Bundle\Api\Data\OptionInterface[] $bundleProductOptions
     * @return $this
     */
    public function setBundleProductOptions($bundleProductOptions)
    {
        $this->setData('bundle_product_options', $bundleProductOptions);
        return $this;
    }

    /**
     * @return \Magento\CatalogInventory\Api\Data\StockItemInterface|null
     */
    public function getStockItem()
    {
        return $this->_get('stock_item');
    }

    /**
     * @param \Magento\CatalogInventory\Api\Data\StockItemInterface $stockItem
     * @return $this
     */
    public function setStockItem(\Magento\CatalogInventory\Api\Data\StockItemInterface $stockItem)
    {
        $this->setData('stock_item', $stockItem);
        return $this;
    }

    /**
     * @return \Magento\Downloadable\Api\Data\LinkInterface[]|null
     */
    public function getDownloadableProductLinks()
    {
        return $this->_get('downloadable_product_links');
    }

    /**
     * @param \Magento\Downloadable\Api\Data\LinkInterface[] $downloadableProductLinks
     * @return $this
     */
    public function setDownloadableProductLinks($downloadableProductLinks)
    {
        $this->setData('downloadable_product_links', $downloadableProductLinks);
        return $this;
    }

    /**
     * @return \Magento\Downloadable\Api\Data\SampleInterface[]|null
     */
    public function getDownloadableProductSamples()
    {
        return $this->_get('downloadable_product_samples');
    }

    /**
     * @param \Magento\Downloadable\Api\Data\SampleInterface[] $downloadableProductSamples
     * @return $this
     */
    public function setDownloadableProductSamples($downloadableProductSamples)
    {
        $this->setData('downloadable_product_samples', $downloadableProductSamples);
        return $this;
    }

    /**
     * @return \Magento\ConfigurableProduct\Api\Data\OptionInterface[]|null
     */
    public function getConfigurableProductOptions()
    {
        return $this->_get('configurable_product_options');
    }

    /**
     * @param \Magento\ConfigurableProduct\Api\Data\OptionInterface[] $configurableProductOptions
     * @return $this
     */
    public function setConfigurableProductOptions($configurableProductOptions)
    {
        $this->setData('configurable_product_options', $configurableProductOptions);
        return $this;
    }

    /**
     * @return int[]|null
     */
    public function getConfigurableProductLinks()
    {
        return $this->_get('configurable_product_links');
    }

    /**
     * @param int[] $configurableProductLinks
     * @return $this
     */
    public function setConfigurableProductLinks($configurableProductLinks)
    {
        $this->setData('configurable_product_links', $configurableProductLinks);
        return $this;
    }
}
