<?php

namespace Jh\ImportTest\ListingFilter;

use Jh\Import\Config\Data;
use Jh\Import\ListingFilter\ImportName;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;

/**
 * @author Aydin Hassan <aydin@wearejh.com>
 */
class ImportNameTest extends TestCase
{
    use ProphecyTrait;

    public function testToOptionArray(): void
    {
        $config = $this->prophesize(Data::class);
        $config->getAllImportNames()->willReturn(['stock', 'product']);

        self::assertEquals(
            [
                ['value' => 'stock', 'label' => 'stock'],
                ['value' => 'product', 'label' => 'product'],
            ],
            (new ImportName($config->reveal()))->toOptionArray()
        );
    }
}
