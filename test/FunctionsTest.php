<?php

namespace Jh\ImportTest;

use PHPUnit\Framework\TestCase;

/**
 * @author Aydin Hassan <aydin@hotmail.co.uk>
 */
class FunctionsTest extends TestCase
{
    /**
     * @dataProvider bytesProvider
     *
     * @param string $bytes
     * @param string $expected
     */
    public function testFormatBytes($bytes, $expected)
    {
        self::assertEquals(format_bytes($bytes), $expected);
    }

    /**
     * @return array
     */
    public function bytesProvider()
    {
        return [
            [1024, '1 KB'],
            [11024, '10.77 KB'],
            [10001024, '9.54 MB'],
            [10001024, '9.54 MB'],
            [10000001024, '9.31 GB'],
        ];
    }
}
