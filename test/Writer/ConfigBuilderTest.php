<?php

namespace Jh\ImportTest\Writer;

use Jh\Import\Import\Record;
use Jh\ImportTest\Asset\ProductExtension;
use Jh\Import\Writer\ConfigBuilder;
use Jh\UnitTestHelpers\ObjectHelper;
use Magento\Catalog\Api\Data\ProductAttributeInterface;
use Magento\Catalog\Api\Data\ProductSearchResultsInterface;
use Magento\Catalog\Api\ProductAttributeRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ProductRepository;
use Magento\ConfigurableProduct\Api\Data\OptionValueInterface;
use Magento\Eav\Api\Data\AttributeOptionInterface;
use Magento\Framework\Api\SearchCriteria;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\ConfigurableProduct\Api\Data\OptionValueInterfaceFactory;
use Magento\ConfigurableProduct\Helper\Product\Options\Factory as OptionFactory;
use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;

/**
 * @author Aydin Hassan <aydin@wearejh.com>
 */
class ConfigBuilderTest extends TestCase
{
    use ObjectHelper;

    private $optionValueFactory;

    private $optionValues = [];

    public function setUp(): void
    {
        $this->optionValueFactory = $this->prophesize(OptionValueInterfaceFactory::class);
    }

    public function testConfigBuilder(): void
    {
        $productRepo = $this->prophesize(ProductRepository::class);
        $searchCriteriaBuilder = $this->prophesize(SearchCriteriaBuilder::class);
        $searchCriteria = $this->prophesize(SearchCriteria::class);
        $optionFactory = $this->prophesize(OptionFactory::class);
        $attributeRepo = $this->prophesize(ProductAttributeRepositoryInterface::class);

        $configBuilder = new ConfigBuilder(
            $productRepo->reveal(),
            $searchCriteriaBuilder->reveal(),
            $optionFactory->reveal(),
            $attributeRepo->reveal(),
            $this->optionValueFactory->reveal()
        );

        $searchCriteriaBuilder
            ->addFilter('sku', ['PROD1', 'PROD2'], 'in')
            ->willReturn($searchCriteriaBuilder->reveal());

        $searchCriteriaBuilder
            ->create()
            ->willReturn($searchCriteria);

        $simple1 = $this->prophesize(Product::class);
        $simple1->getId()->willReturn(31);
        $simple2 = $this->prophesize(Product::class);
        $simple2->getId()->willReturn(33);

        $searchResults = $this->prophesize(ProductSearchResultsInterface::class);
        $searchResults->getItems()->willReturn([$simple1->reveal(), $simple2->reveal()]);

        $productRepo->getList($searchCriteria)->willReturn($searchResults);

        $sizeAttribute = $this->mockAttribute('Size', 'size', 13, [10, 11, 12]);
        $colorAttribute = $this->mockAttribute('Colour', 'colour', 16, [7, 8, 9]);

        $this->optionValueFactory->create()->willReturn(...$this->optionValues);

        $attributeRepo->get('size')->willReturn($sizeAttribute->reveal());
        $attributeRepo->get('colour')->willReturn($colorAttribute->reveal());

        $extension = new ProductExtension();
        $extensionFactory = $this->prophesize(\Magento\Framework\Api\ExtensionAttributesFactory::class);
        $extensionFactory->create(\Magento\Catalog\Model\Product::class, [])->willReturn($extension);
        $product = $this->getObject(Product::class, [
            'extensionFactory' => $extensionFactory->reveal()
        ]);

        /** @var Product $product */
        $record = new Record(10, ['simple_products' => ['PROD1', 'PROD2'], 'config_attributes' => ['Size', 'Colour']]);

        $option1 = $this->prophesize(\Magento\ConfigurableProduct\Api\Data\OptionInterface::class);
        $option2 = $this->prophesize(\Magento\ConfigurableProduct\Api\Data\OptionInterface::class);

        $optionFactoryExpects = [
            [
                'label' => 'Size',
                'attribute_id' => 13,
                'code' => 'size',
                'values' => [
                    $this->optionValues[0]->reveal(),
                    $this->optionValues[1]->reveal(),
                    $this->optionValues[2]->reveal(),
                ]
            ],
            [
                'label' => 'Colour',
                'attribute_id' => 16,
                'code' => 'colour',
                'values' => [
                    $this->optionValues[3]->reveal(),
                    $this->optionValues[4]->reveal(),
                    $this->optionValues[5]->reveal(),
                ]
            ]
        ];

        $optionFactory
            ->create($optionFactoryExpects)
            ->willReturn([$option1->reveal(), $option2->reveal()])
            ->shouldBeCalled();

        $configBuilder->build($record, $product);

        self::assertSame($extension, $product->getExtensionAttributes());
        self::assertSame([31, 33], $product->getExtensionAttributes()->getConfigurableProductLinks());
        self::assertCount(2, $product->getExtensionAttributes()->getConfigurableProductLinks());
        self::assertSame(
            [$option1->reveal(), $option2->reveal()],
            $product->getExtensionAttributes()->getConfigurableProductOptions()
        );
    }

    private function mockAttribute(string $label, string $code, int $id, array $options = []): ObjectProphecy
    {
        $attribute = $this->prophesize(ProductAttributeInterface::class);
        $attribute->getAttributeId()->willReturn($id);
        $attribute->getDefaultFrontendLabel()->willReturn($label);
        $attribute->getAttributeCode()->willReturn($code);

        $attribute->getOptions()->willReturn(array_map(function ($optionValue) {
            $this->createExpectationForOptionValueFactory($optionValue);


            $attributeOption = $this->prophesize(AttributeOptionInterface::class);
            $attributeOption->getValue()->willReturn($optionValue);
            return $attributeOption->reveal();
        }, $options));

        return $attribute;
    }

    private function createExpectationForOptionValueFactory(int $value): void
    {
        $optionValue = $this->prophesize(OptionValueInterface::class);
        $optionValue->setValueIndex($value)
            ->will(function ($args, $mock) {
                $mock->getValueIndex()->willReturn($args[0]);
            })
            ->willReturn($optionValue->reveal());

        $this->optionValues[] = $optionValue;
    }
}
