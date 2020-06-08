<?php

declare(strict_types=1);

namespace Jh\Import\Import;

use Jh\Import\Config;

interface RequiresPreparation
{
    public function prepare(Config $config): void;
}
