<?php

namespace Jh\ImportTest\Import;

use Jh\Import\Import\Result;
use PHPUnit\Framework\TestCase;

/**
 * @author Aydin Hassan <aydin@hotmail.co.uk>
 */
class ResultTest extends TestCase
{
    public function testGetters()
    {
        $result = new Result([]);
        self::assertEquals([], $result->getAffectedIds());
        self::assertFalse($result->hasAffectedIds());

        $result = new Result([1, 2, 3, 4]);
        self::assertEquals([1, 2, 3, 4], $result->getAffectedIds());
        self::assertTrue($result->hasAffectedIds());
    }
}
