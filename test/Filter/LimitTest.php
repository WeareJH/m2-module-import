<?php

namespace Jh\ImportTest\Import;

use Jh\Import\Filter\Limit;
use Jh\Import\Import\Record;
use PHPUnit\Framework\TestCase;

/**
 * @author Aydin Hassan <aydin@wearejh.com>
 */
class LimitTest extends TestCase
{
    /**
     * @param int $limit
     * @param int $recordNumber
     * @param bool $expected
     * @return void
     *
     * @dataProvider limitProvider
     */
    public function testLimit(int $limit, int $recordNumber, bool $expected)
    {
        $record = new Record($recordNumber, []);
        self::assertEquals($expected, (new Limit($limit))->__invoke($record));
    }

    public function limitProvider()
    {
        return [
            [100, 99, true],
            [100, 100, true],
            [100, 101, false],
            [100, 200, false],
        ];
    }
}
