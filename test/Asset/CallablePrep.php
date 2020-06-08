<?php

declare(strict_types=1);

namespace Jh\ImportTest\Asset;

use Jh\Import\Config;
use Jh\Import\Import\RequiresPreparation;

class CallablePrep implements RequiresPreparation
{
    public function __invoke()
    {
    }

    public function prepare(Config $config): void
    {
    }
}
