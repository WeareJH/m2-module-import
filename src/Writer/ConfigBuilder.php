<?php

namespace Jh\Import\Writer;

use Jh\Import\Import\Record;
use Magento\Catalog\Api\ProductAttributeRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ProductRepository;
use Magento\ConfigurableProduct\Api\Data\OptionInterfaceFactory;
use Magento\ConfigurableProduct\Api\Data\OptionValueInterfaceFactory;
use Magento\Eav\Api\Data\AttributeOptionInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\ConfigurableProduct\Helper\Product\Options\Factory as OptionFactory;

/**
 * @author Aydin Hassan <aydin@wearejh.com>
 */
class ConfigBuilder
{
    /**
     * @var ProductRepository
     */
    private $productRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var OptionFactory
     */
    private $optionFactory;

    /**
     * @var ProductAttributeRepositoryInterface
     */
    private $productAttributeRepository;

    /**
     * @var OptionValueInterfaceFactory
     */
    private $optionValueFactory;

    /**
     * @param ProductRepository $productRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param OptionFactory $optionFactory
     * @param ProductAttributeRepositoryInterface $productAttributeRepository
     * @param OptionValueInterfaceFactory $optionValueFactory
     */
    public function __construct(
        ProductRepository $productRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        OptionFactory $optionFactory,
        ProductAttributeRepositoryInterface $productAttributeRepository,
        OptionValueInterfaceFactory $optionValueFactory
    ) {
        $this->productRepository = $productRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->optionFactory = $optionFactory;
        $this->productAttributeRepository = $productAttributeRepository;
        $this->optionValueFactory = $optionValueFactory;
    }

    public function build(Record $record, Product $product)
    {
        $simpleSkus = $record->getColumnValue('simple_products', [], 'array');

        $criteria = $this->searchCriteriaBuilder
            ->addFilter('sku', $simpleSkus, 'in')
            ->create();

        $simples = $this->productRepository->getList($criteria);

        $optionData = collect($record->getColumnValue('config_attributes', [], 'array'))
            ->map(function ($attributeName) {
                return strtolower(str_replace(' ', '', $attributeName));
            })
            ->map(function ($attributeCode) {
                $attribute = $this->productAttributeRepository->get($attributeCode);

                $values = collect($attribute->getOptions())
                    ->filter(function (AttributeOptionInterface $option) {
                        return $option->getValue();
                    })
                    ->map(function (AttributeOptionInterface $option) {
                        $optionValue = $this->optionValueFactory->create();
                        return $optionValue->setValueIndex($option->getValue());
                    });

                return [
                    'label'         => $attribute->getDefaultFrontendLabel(),
                    'attribute_id'  => $attribute->getAttributeId(),
                    'code'          => $attribute->getAttributeCode(),
                    'values'        => $values->all()
                ];
            })
            ->all();

        $configurableOptions = $this->optionFactory->create($optionData);

        $productIds = collect($simples->getItems())
            ->map(function (Product $product) {
                return $product->getId();
            })
            ->all();

        $extensionAttributes = $product
            ->getExtensionAttributes()
            ->setConfigurableProductOptions($configurableOptions)
            ->setConfigurableProductLinks($productIds);

        $product->setExtensionAttributes($extensionAttributes);
    }
}
