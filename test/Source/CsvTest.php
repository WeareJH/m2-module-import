<?php

namespace Jh\ImportTest\Source;

use Jh\Import\Report\CollectingReport;
use Jh\Import\Report\Handler\CollectingHandler;
use Jh\Import\Report\Report;
use Jh\Import\Source\Csv;
use PHPUnit\Framework\TestCase;

/**
 * @author Aydin Hassan <aydin@wearejh.com>
 */
class CsvTest extends TestCase
{
    public function testCount()
    {
        $tempFile = tempnam(sys_get_temp_dir(), $this->getName());
        file_put_contents($tempFile, implode("\n", [
            'column1,column2,column3',
            'row1column1value,row1column2value,row1column3value',
            'row2column1value,row2column2value,row2column3value',
            'row3column1value,row3column2value,row3column3value',
        ]));

        $csvSource = new Csv($tempFile);
        $csvSource->traverse(
            function () {
            },
            function () {
            },
            new Report([], 'product', 'some-source-id')
        );

        self::assertEquals(3, $csvSource->count());
        unlink($tempFile);
    }

    public function testGetFile()
    {
        $tempFile = tempnam(sys_get_temp_dir(), $this->getName());
        file_put_contents($tempFile, implode("\n", [
            'column1,column2,column3',
            'row1column1value,row1column2value,row1column3value',
            'row2column1value,row2column2value,row2column3value',
            'row3column1value,row3column2value,row3column3value',
        ]));

        $csvSource = new Csv($tempFile);
        $csvSource->traverse(
            function () {
            },
            function () {
            },
            new Report([], 'product', 'some-source-id')
        );

        self::assertInstanceOf(\SplFileObject::class, $csvSource->getFile());
        self::assertEquals($tempFile, $csvSource->getFile()->getRealPath());
        unlink($tempFile);
    }

    public function testParseCsvCallsCallableForEachRow()
    {
        $tempFile = tempnam(sys_get_temp_dir(), $this->getName());
        file_put_contents($tempFile, implode("\n", [
            'column1,column2,column3',
            'row1column1value,row1column2value,row1column3value',
            'row2column1value,row2column2value,row2column3value',
            'row3column1value,row3column2value,row3column3value',
        ]));

        $expectedData = [
            2 => ['column1' => 'row1column1value', 'column2' => 'row1column2value', 'column3' => 'row1column3value'],
            3 => ['column1' => 'row2column1value', 'column2' => 'row2column2value', 'column3' => 'row2column3value'],
            4 => ['column1' => 'row3column1value', 'column2' => 'row3column2value', 'column3' => 'row3column3value'],
        ];

        $parsed = [];
        $csvSource = new Csv($tempFile);
        $csvSource->traverse(
            function ($rowId, $row) use (&$parsed) {
                $parsed[$rowId] = $row;
            },
            function () {
            },
            new Report([], 'product', 'some-source-id')
        );

        self::assertEquals($expectedData, $parsed);
        unlink($tempFile);
    }

    public function testParseCsvCallsErrorCallableForEachErroneousRowAndAddsAnEntryToTheReport()
    {
        $tempFile = tempnam(sys_get_temp_dir(), $this->getName());
        file_put_contents($tempFile, implode("\n", [
            'column1,column2,column3',
            'row1column1value,row1column2value,row1column3value',
            'row2column1value,row2column2value',
            'row3column1value,row3column2value',
        ]));

        $expectedData = [
            2 => ['column1' => 'row1column1value', 'column2' => 'row1column2value', 'column3' => 'row1column3value'],
        ];
        
        $parsed = [];
        $errors = [];
        $csvSource = new Csv($tempFile);
        $csvSource->traverse(
            function ($rowId, $row) use (&$parsed) {
                $parsed[$rowId] = $row;
            },
            function ($rowNumber) use (&$errors) {
                $errors[] = $rowNumber;
            },
            new Report([$handler = new CollectingHandler], 'product', 'some-source-id')
        );

        self::assertEquals($expectedData, $parsed);
        self::assertEquals([2, 3], $errors);
        self::assertEquals(
            [
                [
                    'log_level' => 'ERROR',
                    'message'  => 'Column count does not match header count on row: "2"',
                ],
                [
                    'log_level' => 'ERROR',
                    'message'  => 'Column count does not match header count on row: "3"',
                ]
            ],
            $handler->messages
        );
        unlink($tempFile);
    }

    public function testSourceIdReturnsSameIdForSameFile()
    {
        $tempFile = tempnam(sys_get_temp_dir(), $this->getName());
        file_put_contents($tempFile, implode("\n", [
            'column1,column2,column3',
            'row1column1value,row1column2value,row1column3value',
            'row2column1value,row2column2value',
            'row3column1value,row3column2value',
        ]));

        $firstId  = (new Csv($tempFile))->getSourceId();
        $secondId = (new Csv($tempFile))->getSourceId();

        self::assertEquals($firstId, $secondId);
    }

    public function testSourceIdReturnsDifferentIdForDifferentFile()
    {
        $tempFile1 = tempnam(sys_get_temp_dir(), $this->getName());
        $tempFile2 = tempnam(sys_get_temp_dir(), $this->getName());
        file_put_contents($tempFile1, implode("\n", [
            'column1,column2,column3',
            'row1column1value,row1column2value,row1column3value',
            'row2column1value,row2column2value',
            'row3column1value,row3column2value',
        ]));
        file_put_contents($tempFile2, 'Second File');

        $firstId  = (new Csv($tempFile1))->getSourceId();
        $secondId = (new Csv($tempFile2))->getSourceId();

        self::assertNotEquals($firstId, $secondId);
    }
}
