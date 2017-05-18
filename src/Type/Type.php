<?php

namespace Jh\Import\Type;

use Jh\Import\Config;

/**
 * @author Aydin Hassan <aydin@wearejh.com>
 */
interface Type
{
    public function run(Config $config);
}
