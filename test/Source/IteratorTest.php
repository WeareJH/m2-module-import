<?php

namespace Jh\ImportTest\Source;

use Jh\Import\Report\CollectingReport;
use Jh\Import\Report\Report;
use Jh\Import\Source\Iterator;
use PHPUnit\Framework\TestCase;

/**
 * @author Aydin Hassan <aydin@wearejh.com>
 */
class IteratorTest extends TestCase
{
    public function testCount(): void
    {
        $source = new Iterator(new \ArrayIterator([1, 2, 3]));
        $source->traverse(
            function () {
            },
            function () {
            },
            new Report([], 'product', 'some-source-id')
        );

        self::assertEquals(3, $source->count());
    }

    public function testTraverseCallsCallableForEachRow(): void
    {
        $expectedData = [
            1 => ['column1' => 'row1column1value', 'column2' => 'row1column2value', 'column3' => 'row1column3value'],
            2 => ['column1' => 'row2column1value', 'column2' => 'row2column2value', 'column3' => 'row2column3value'],
            3 => ['column1' => 'row3column1value', 'column2' => 'row3column2value', 'column3' => 'row3column3value'],
        ];

        $source = new Iterator(new \ArrayIterator($expectedData));

        $parsed = [];
        $source->traverse(
            function ($rowId, $row) use (&$parsed) {
                $parsed[$rowId] = $row;
            },
            function () {
            },
            new Report([], 'product', 'some-source-id')
        );

        self::assertEquals($expectedData, $parsed);
    }

    public function testTraverseCallsCallableForEachRowUsingGenerator(): void
    {
        $expectedData = [
            1 => ['column1' => 'row1column1value', 'column2' => 'row1column2value', 'column3' => 'row1column3value'],
            2 => ['column1' => 'row2column1value', 'column2' => 'row2column2value', 'column3' => 'row2column3value'],
            3 => ['column1' => 'row3column1value', 'column2' => 'row3column2value', 'column3' => 'row3column3value'],
        ];

        $source = Iterator::fromCallable(function () use ($expectedData) {
            foreach ($expectedData as $key => $value) {
                yield $key => $value;
            }
        });

        $parsed = [];
        $source->traverse(
            function ($rowId, $row) use (&$parsed) {
                $parsed[$rowId] = $row;
            },
            function () {
            },
            new Report([], 'product', 'some-source-id')
        );

        self::assertEquals($expectedData, $parsed);
    }

    public function testSourceIdReturnsDifferentIdForDifferentIterator(): void
    {
        $source1 = Iterator::fromCallable(function () {
            foreach ([1, 2, 3] as $key => $value) {
                yield $key => $value;
            }
        });

        $source2 = Iterator::fromCallable(function () {
            foreach ([1, 2, 3] as $key => $value) {
                yield $key => $value;
            }
        });

        self::assertNotEquals($source1->getSourceId(), $source2->getSourceId());
    }

    public function testSourceIdReturnsSameIdForSameFile(): void
    {
        $source = Iterator::fromCallable(function () {
            foreach ([1, 2, 3] as $key => $value) {
                yield $key => $value;
            }
        });

        self::assertEquals($source->getSourceId(), $source->getSourceId());
    }
}
