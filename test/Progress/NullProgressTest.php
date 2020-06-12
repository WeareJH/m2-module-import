<?php

namespace Jh\ImportTest\Progress;

use Jh\Import\Config;
use Jh\Import\Progress\NullProgress;
use Jh\Import\Source\Source;
use PHPUnit\Framework\TestCase;

/**
 * @author Aydin Hassan <aydin@wearejh.com>
 */
class NullProgressTest extends TestCase
{
    public function testNullProgressDoesNothing()
    {
        $this->expectOutputString('');

        $source = $this->prophesize(Source::class);
        $nullProgress = new NullProgress();
        $nullProgress->start($source->reveal(), new Config('product', []));
        $nullProgress->advance();
        $nullProgress->finish($source->reveal());
    }
}
