<?php

namespace Jh\ImportTest\Transformer;

use Jh\Import\Import\Record;
use Jh\Import\Transformer\YesNoTransformer;
use PHPUnit\Framework\TestCase;
use stdClass;

/**
 * @author Aydin Hassan <aydin@wearejh.com>
 */
class YesNoTransformerTest extends TestCase
{
    /**
     * @dataProvider truthyValue
     */
    public function testyesNoWithTruthyValueIsSetToEnabled($value): void
    {
        $record = new Record(11, ['is_cool_product' => $value]);

        $transformer = new YesNoTransformer('is_cool_product');
        $transformer->__invoke($record);

        self::assertEquals(1, $record->getColumnValue('is_cool_product'));
    }

    /**
     * @return array
     */
    public function truthyValue(): array
    {
        return [
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
    public function testYesNoWithFalseyValueIsSetToDisabled($value): void
    {
        $record = new Record(11, ['is_cool_product' => $value]);

        $transformer = new YesNoTransformer('is_cool_product');
        $transformer->__invoke($record);

        self::assertEquals(0, $record->getColumnValue('status'));
    }

    /**
     * @return array
     */
    public function falseyValue(): array
    {
        return [
            ['No'],
            ['no'],
            ['False'],
            ['false'],
            [0],
            ['0'],
        ];
    }

    public function testYesNoWithUnknownValueIsSetToNo(): void
    {
        $record = new Record(11, ['is_cool_product' => new stdClass()]);

        $transformer = new YesNoTransformer('is_cool_product');
        $transformer->__invoke($record);

        self::assertEquals(0, $record->getColumnValue('is_cool_product'));
    }
}
