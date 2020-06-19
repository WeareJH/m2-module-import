<?php

namespace Jh\ImportTest\Import;

use Jh\Import\Import\Result;
use PHPUnit\Framework\TestCase;

class ResultTest extends TestCase
{
    public function testGetters(): void
    {
        $result = new Result([]);
        self::assertEquals([], $result->getAffectedIds());
        self::assertFalse($result->hasAffectedIds());

        $result = new Result([1, 2, 3, 4]);
        self::assertEquals([1, 2, 3, 4], $result->getAffectedIds());
        self::assertTrue($result->hasAffectedIds());
    }

    public function testCount(): void
    {
        $result = new Result([1, 2, 4]);
        self::assertEquals(3, $result->affectedIdsCount());
    }
}
