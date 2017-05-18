<?php

namespace Jh\ImportTest\Transformer;

use Jh\Import\Import\Record;
use Jh\Import\Transformer\ProductStatusTransformer;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use PHPUnit\Framework\TestCase;

/**
 * @author Aydin Hassan <aydin@wearejh.com>
 */
class ProductStatusTransformerTest extends TestCase
{
    /**
     * @dataProvider truthyValue
     */
    public function testProductStatusWithTruthyValueIsSetToEnabled($value)
    {
        $record = new Record(11, ['status' => $value]);

        $transformer = new ProductStatusTransformer('status');
        $transformer->__invoke($record);

        self::assertEquals(Status::STATUS_ENABLED, $record->getColumnValue('status'));
    }

    /**
     * @return array
     */
    public function truthyValue()
    {
        return [
            ['Enabled'],
            ['enabled'],
            ['Yes'],
            ['yes'],
            ['True'],
            ['true'],
            [1],
            ['1'],
        ];
    }

    /**
     * @dataProvider falseyValue
     */
    public function testProductStatusWithFalseyValueIsSetToDisabled($value)
    {
        $record = new Record(11, ['status' => $value]);

        $transformer = new ProductStatusTransformer('status');
        $transformer->__invoke($record);

        self::assertEquals(Status::STATUS_DISABLED, $record->getColumnValue('status'));
    }

    /**
     * @return array
     */
    public function falseyValue()
    {
        return [
            ['Disabled'],
            ['disabled'],
            ['No'],
            ['no'],
            ['False'],
            ['false'],
            [0],
            ['0'],
        ];
    }

    public function testProductStatusWithUnknownValueIsSetToDisabled()
    {
        $record = new Record(11, ['status' => new \stdClass]);

        $transformer = new ProductStatusTransformer('status');
        $transformer->__invoke($record);

        self::assertEquals(Status::STATUS_DISABLED, $record->getColumnValue('status'));
    }
}
