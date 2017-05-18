<?php

namespace Jh\ImportTest\Transformer;

use Jh\Import\Import\Record;
use Jh\Import\Report\Handler\CollectingHandler;
use Jh\Import\Report\ReportItem;
use Jh\Import\Transformer\ProductVisibilityTransformer;
use Magento\Catalog\Model\Product\Visibility;
use PHPUnit\Framework\TestCase;

/**
 * @author Aydin Hassan <aydin@wearejh.com>
 */
class ProductVisibilityTransformerTest extends TestCase
{
    /**
     * @dataProvider visibilityProvider
     * @param string $visibility
     */
    public function testVisibilityTransformer(string $visibility, $id)
    {
        $record = new Record(11, ['visibility' => $visibility]);

        $transformer = new ProductVisibilityTransformer('visibility');
        $transformer->__invoke($record, new ReportItem([], 100, 'sku', 100));

        self::assertEquals($id, $record->getColumnValue('visibility'));
    }

    public function visibilityProvider()
    {
        return [
            ['Not Visible Individually', Visibility::VISIBILITY_NOT_VISIBLE],
            ['Catalog', Visibility::VISIBILITY_IN_CATALOG],
            ['Search', Visibility::VISIBILITY_IN_SEARCH],
            ['Catalog, Search', Visibility::VISIBILITY_BOTH],
        ];
    }

    public function testUnknownVisibilityIsSetToNull()
    {
        $record = new Record(11, ['visibility' => 'unknown-visibility']);

        $transformer = new ProductVisibilityTransformer('visibility');
        $transformer->__invoke($record, new ReportItem([$handler = new CollectingHandler], 100, 'sku', 100));

        self::assertNull($record->getColumnValue('visibility'));
        self::assertSame(
            [
                [
                    'log_level' => 'WARNING',
                    'message' => 'Product Visibility "unknown-visibility" is invalid and/or not supported.'
                ]
            ],
            $handler->itemMessages
        );
    }

    public function testDefaultValueIsUsedIfSpecifiedAndValueIsMissing()
    {
        $record = new Record(11, ['visibility' => null]);

        $transformer = new ProductVisibilityTransformer('visibility', Visibility::VISIBILITY_BOTH);
        $transformer->__invoke($record, new ReportItem([$handler = new CollectingHandler], 100, 'sku', 100));

        self::assertEquals(Visibility::VISIBILITY_BOTH, $record->getColumnValue('visibility'));
    }
}
