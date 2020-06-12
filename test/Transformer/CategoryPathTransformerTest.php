<?php

namespace Jh\ImportTest\Transformer;

use Jh\Import\Import\Record;
use Jh\Import\Report\Handler\CollectingHandler;
use Jh\Import\Report\ReportItem;
use Jh\Import\Transformer\CategoryPathTransformer;
use Magento\Catalog\Api\Data\CategoryTreeInterface;
use Magento\Catalog\Model\CategoryManagement;
use PHPUnit\Framework\TestCase;

/**
 * @author Aydin Hassan <aydin@wearejh.com>
 */
class CategoryPathTransformerTest extends TestCase
{
    public function testTransformCategoryPath()
    {
        $categoryManagement = $this->prophesize(CategoryManagement::class);
        $categoryTree       = $this->prophesize(CategoryTreeInterface::class);

        $categoryManagement->getTree()->willReturn($categoryTree);

        $level1       = $this->prophesize(CategoryTreeInterface::class);
        $level1->getName()->willReturn('level1');

        $level2       = $this->prophesize(CategoryTreeInterface::class);
        $level2->getName()->willReturn('level2');

        $someCategory = $this->prophesize(CategoryTreeInterface::class);
        $someCategory->getName()->willReturn('some-category');
        $someCategory->getId()->willReturn(5);

        $categoryTree->getChildrenData()->willReturn([$level1->reveal()]);
        $level1->getChildrenData()->willReturn([$level2->reveal()]);
        $level2->getChildrenData()->willReturn([$someCategory->reveal()]);

        $record = new Record(11, ['categories' => ['level1/level2/some-category']]);

        $transformer = new CategoryPathTransformer($categoryManagement->reveal());
        $transformer->__invoke($record, new ReportItem([$handler = new CollectingHandler()], 100, 'sku', 100));

        self::assertEquals([5], $record->getColumnValue('categories'));
        self::assertCount(0, $handler->itemMessages);
    }

    public function testTransformCategoryLogsIfCategoryDoesNotExist()
    {
        $categoryManagement = $this->prophesize(CategoryManagement::class);
        $categoryTree       = $this->prophesize(CategoryTreeInterface::class);

        $categoryManagement->getTree()->willReturn($categoryTree);

        $level1       = $this->prophesize(CategoryTreeInterface::class);
        $level1->getName()->willReturn('level1');

        $level2       = $this->prophesize(CategoryTreeInterface::class);
        $level2->getName()->willReturn('level2');

        $categoryTree->getChildrenData()->willReturn([$level1->reveal()]);
        $level1->getChildrenData()->willReturn([$level2->reveal()]);
        $level2->getChildrenData()->willReturn([]);

        $record = new Record(11, ['categories' => ['level1/level2/some-category']]);

        $transformer = new CategoryPathTransformer($categoryManagement->reveal());
        $transformer->__invoke($record, new ReportItem([$handler = new CollectingHandler()], 100, 'sku', 100));

        self::assertEmpty($record->getColumnValue('categories'));
        self::assertSame(
            [
                [
                    'log_level' => 'WARNING',
                    'message' => 'Category: "some-category" does not exist.'
                ]
            ],
            $handler->itemMessages
        );
    }
}
