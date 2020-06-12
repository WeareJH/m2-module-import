<?php

namespace Jh\ImportTest\Transformer;

use Jh\Import\Import\Record;
use Jh\Import\Report\Handler\CollectingHandler;
use Jh\Import\Report\ReportItem;
use Jh\Import\Transformer\ProductTypeTransformer;
use PHPUnit\Framework\TestCase;
use Magento\Catalog\Model\Product\Type;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;

/**
 * @author Aydin Hassan <aydin@wearejh.com>
 */
class ProductTypeTransformerTest extends TestCase
{
    /**
     * @dataProvider simpleValue
     */
    public function testProductTypeWithSimple($value)
    {
        $record = new Record(11, ['type' => $value]);

        $transformer = new ProductTypeTransformer('type');
        $transformer->__invoke($record, new ReportItem([], 100, 'sku', 100));

        self::assertEquals(Type::TYPE_SIMPLE, $record->getColumnValue('type'));
    }

    /**
     * @return array
     */
    public function simpleValue()
    {
        return [
            ['simple'],
            ['Simple'],
        ];
    }

    /**
     * @dataProvider configValue
     */
    public function testProductTypeWithConfig($value)
    {
        $record = new Record(11, ['type' => $value]);

        $transformer = new ProductTypeTransformer('type');
        $transformer->__invoke($record, new ReportItem([], 100, 'sku', 100));

        self::assertEquals(Configurable::TYPE_CODE, $record->getColumnValue('type'));
    }

    /**
     * @return array
     */
    public function configValue()
    {
        return [
            ['configurable'],
            ['Configurable'],
        ];
    }

    public function testUnknownTypeIsSetToNullAndLogged()
    {
        $record = new Record(11, ['type' => 'unknown-type']);

        $transformer = new ProductTypeTransformer('type');
        $transformer->__invoke($record, new ReportItem([$handler = new CollectingHandler()], 100, 'sku', 100));

        self::assertNull($record->getColumnValue('type'));
        self::assertSame(
            [
                [
                    'log_level' => 'WARNING',
                    'message' => 'Product Type "unknown-type" is invalid and/or not supported.'
                ]
            ],
            $handler->itemMessages
        );
    }
}
