<?php

namespace Jh\Import\AttributeProcessor;

use Jh\Import\Import\Record;
use Jh\Import\Report\ReportItem;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Eav\AttributeFactory;
use Magento\Eav\Api\Data\AttributeInterface;
use Magento\Eav\Model\AttributeRepository;
use Magento\Eav\Model\Config;
use Magento\Eav\Model\Entity\Attribute\AbstractAttribute;
use Magento\Eav\Model\Entity\Attribute\Source\TableFactory;
use Magento\Eav\Model\ResourceModel\AttributeLoader;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Catalog\Model\ResourceModel\Product as ProductResource;
use Magento\Framework\Exception\StateException;

/**
 * @author Aydin Hassan <aydin@wearejh.com>
 */
class AttributeProcessor
{
    /**
     * @var AttributeRepository
     */
    private $attributeRepository;

    /**
     * @var Config
     */
    private $eavConfig;

    /**
     * @var AttributeInterface[]
     */
    private $attributes = [];

    /**
     * @var string[]
     */
    private $createdAttributes = [];

    /**
     * @var array
     */
    private $attributeOptions = [];

    /**
     * @var AttributeFactory
     */
    private $attributeFactory;

    /**
     * @var ProductResource
     */
    private $productResource;

    /**
     * @var bool
     */
    private $initialised = false;

    /**
     * @var AttributeLoader
     */
    private $attributeLoader;

    /**
     * @var TableFactory
     */
    private $tableFactory;

    /**
     * @var \Magento\Framework\DB\Adapter\AdapterInterface
     */
    private $dbConnection;

    /**
     * @var int
     */
    private $defaultAttributeGroupId;

    /**
     * @var IndividualAttributeProcessor[]
     */
    private $attributeProcessors;

    public function __construct(
        AttributeRepository $attributeRepository,
        Config $eavConfig,
        AttributeLoader $attributeLoader,
        AttributeFactory $attributeFactory,
        ProductResource $productResource,
        TableFactory $tableFactory,
        ResourceConnection $resourceConnection,
        array $attributeProcessors = []
    ) {
        $this->attributeRepository = $attributeRepository;
        $this->eavConfig = $eavConfig;
        $this->attributeFactory = $attributeFactory;
        $this->productResource = $productResource;
        $this->attributeLoader = $attributeLoader;
        $this->tableFactory = $tableFactory;
        $this->dbConnection = $resourceConnection->getConnection();
        $this->attributeProcessors = $attributeProcessors;

        $select = $this->dbConnection
            ->select()
            ->from('eav_attribute_group', 'attribute_group_id')
            ->where('attribute_set_id = :attribute_set_id')
            ->order(['default_id DESC sort_order'])
            ->limit(1);

        $this->defaultAttributeGroupId = $this->dbConnection->fetchOne(
            $select,
            ['attribute_set_id' => $this->productResource->getEntityType()->getDefaultAttributeSetId()]
        );
    }

    public function setAttributeValue(
        Product $product,
        $attributeNameOrCode,
        $attributeValue,
        Record $record,
        ReportItem $report
    ) {
        //some attributes will come in as their name and not their code -
        //so we need to normalise
        $attributeCode = strtolower(str_replace(' ', '_', $attributeNameOrCode));

        try {
            $attribute = $this->findAttribute($attributeCode);
        } catch (NoSuchEntityException $e) {
            $attributeName = $attributeNameOrCode;
            //attribute doesn't exist
            //so we create a text attribute with the code - and set the value on the product

            try {
                $this->createAttribute($attributeCode, $attributeName);
                $product->setData($attributeCode, $attributeValue);
                $report->addDebug(
                    sprintf('Created new text attribute named: "%s"', $attributeCode)
                );
            } catch (StateException $e) {
                $report->addError(
                    sprintf(
                        'Attempted to created text attribute named: "%s" but failed. Error: "%s"',
                        $attributeCode,
                        $e->getMessage()
                    )
                );
            }

            return;
        }

        if (!$attribute->usesSource()) {
            $product->setData($attributeCode, $attributeValue);
            return;
        }

        try {
            if (isset($this->attributeProcessors[$attributeCode])) {
                $id = $this->attributeProcessors[$attributeCode]
                    ->process($attribute, $attributeValue, $record, $report);
            } else {
                switch ($attribute->getFrontendInput()) {
                    case 'select':
                        $id = $this->findOrCreateOption($attribute, $attributeValue, $report);
                }
            }

            $product->setData($attributeCode, $id);
        } catch (CouldNotCreateOptionException $e) {
        }
    }

    /**
     * @param string $attributeCode
     * @return AbstractAttribute
     * @throws NoSuchEntityException
     */
    private function findAttribute(string $attributeCode) : AbstractAttribute
    {
        if (false === $this->initialised) {
            $attributes = $this->attributeLoader->getAttributes(ProductInterface::class);
            $this->attributes = array_combine(
                array_map(function (AttributeInterface $attribute) {
                    return $attribute->getAttributeCode();
                }, $attributes),
                $attributes
            );
            $this->initialised = true;
        }

        if (!isset($this->attributes[$attributeCode])) {
            throw new NoSuchEntityException;
        }

        return $this->attributes[$attributeCode];
    }

    private function createAttribute($attributeCode, $attributeName)
    {
        $attribute = $this->attributeFactory->create();
        $attribute->addData([
            'frontend_label'          => $attributeName,
            'attribute_code'          => $attributeCode,
            'frontend_input'          => 'text',
            'backend_type'            => 'varchar',
            'type'                    => 'text',
            'source_model'            => null,
            'backend_model'           => null,
            'is_user_defined'         => 1,
            'is_filterable'           => 1,
            'is_filterable_in_search' => 1,
            'apply_to'                => null,
            'user_defined'            => 1,
        ]);

        $attribute->setEntityType(Product::ENTITY);
        $attribute->setEntityTypeId($this->productResource->getEntityType()->getEntityTypeId());
        $attribute->setAttributeSetId($this->productResource->getEntityType()->getDefaultAttributeSetId());
        $attribute->setData('attribute_group_id', $this->defaultAttributeGroupId);

        $this->attributeRepository->save($attribute);

        $this->createdAttributes[] = $attributeCode;
        $this->attributes[$attributeCode] = $attribute;
    }

    private function findOrCreateOption(AttributeInterface $attribute, string $attributeValue, ReportItem $reportItem)
    {
        if (!isset($this->attributeOptions[$attribute->getAttributeCode()])) {
            $this->initialiseAttributeValues($attribute);
        }

        try {
            return $this->getOption($attribute, $attributeValue);
        } catch (NoSuchEntityException $e) {
            return $this->createOption($attribute, $attributeValue, $reportItem);
        }
    }

    private function createOption(AttributeInterface $attribute, string $attributeValue, ReportItem $report)
    {
        $this->dbConnection->insert(
            $this->dbConnection->getTableName('eav_attribute_option'),
            [
                'attribute_id' => $attribute->getAttributeId(),
                'sort_order'   => 0
            ]
        );

        $this->dbConnection->insert(
            $this->dbConnection->getTableName('eav_attribute_option_value'),
            [
                'option_id' => $this->dbConnection->lastInsertId(),
                'store_id'  => 0,
                'value'     => $attributeValue
            ]
        );

        $id = $this->dbConnection->lastInsertId();

        $this->addOption($attribute, $attributeValue, $id);

        $report->addDebug(
            sprintf(
                'Created new option for attribute: "%s". Value: "%s". ID: "%s"',
                $attribute->getAttributeCode(),
                $attributeValue,
                $id
            )
        );

        return $id;
    }

    /**
     * @param AttributeInterface $attribute
     * @param string $value
     * @return int
     * @throws NoSuchEntityException
     */
    private function getOption(AttributeInterface $attribute, string $value) : int
    {
        if (isset($this->attributeOptions[$attribute->getAttributeCode()][$value])) {
            return $this->attributeOptions[$attribute->getAttributeCode()][$value];
        }

        throw new NoSuchEntityException;
    }

    /**
     * Add an attribute value to the attribute value/id cache
     *
     * @param AttributeInterface $attribute
     * @param string $value
     * @param int $id
     */
    private function addOption(AttributeInterface $attribute, string $value, int $id)
    {
        if (!isset($this->attributeOptions[$attribute->getAttributeCode()])) {
            $this->attributeOptions[$attribute->getAttributeCode()] = [];
        }

        $this->attributeOptions[$attribute->getAttributeCode()][$value] = $id;
    }

    /**
     * Load the attribute options for this attribute and store them
     * on the instance so we don't hit the database constantly.
     *
     * @param AttributeInterface $attribute
     * @return void
     */
    private function initialiseAttributeValues(AttributeInterface $attribute)
    {
        /** @var \Magento\Eav\Model\Entity\Attribute\Source\Table $sourceModel */
        $sourceModel = $this->tableFactory->create();
        $sourceModel->setAttribute($attribute);

        $this->attributeOptions[$attribute->getAttributeCode()] = [];

        foreach ($sourceModel->getAllOptions(false) as $option) {
            $this->addOption($attribute, $option['label'], $option['value']);
        }
    }

    public function __destruct()
    {
        if (count($this->createdAttributes) > 0) {
            //if we created some attributes we want to flush the each cache
            //so it will see the new attributes
            $this->eavConfig->clear();
        }
    }
}
