<?php

namespace Jh\Import\Progress;

use Jh\Import\Config;
use Jh\Import\Source\Source;

/**
 * @author Aydin Hassan <aydin@wearejh.com>
 */
class NullProgress implements Progress
{
    public function start(Source $source, Config $config): void
    {
    }

    public function advance(): void
    {
    }

    public function finish(Source $source): void
    {
    }
}
