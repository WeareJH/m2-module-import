<?php

namespace Jh\ImportTest\Ui\Component\Listing\Column;

use Jh\Import\Ui\Component\Listing\Column\Level\Options;
use PHPUnit\Framework\TestCase;

/**
 * @author Reka Szekely <reka@wearejh.com>
 */
class LevelOptionsTest extends TestCase
{
    public function testLogLevelOptions(): void
    {
        self::assertEquals([
            [
                'value' => 'DEBUG',
                'label' => 'DEBUG'
            ],
            [
                'value' => 'INFO',
                'label' => 'INFO'
            ],
            [
                'value' => 'NOTICE',
                'label' => 'NOTICE'
            ],
            [
                'value' => 'WARNING',
                'label' => 'WARNING'
            ],
            [
                'value' => 'ERROR',
                'label' => 'ERROR'
            ],
            [
                'value' => 'CRITICAL',
                'label' => 'CRITICAL'
            ],
            [
                'value' => 'ALERT',
                'label' => 'ALERT'
            ],
            [
                'value' => 'EMERGENCY',
                'label' => 'EMERGENCY'
            ]
        ], (new Options())->toOptionArray());
    }
}
