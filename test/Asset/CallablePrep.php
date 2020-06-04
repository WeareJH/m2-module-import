<?php

declare(strict_types=1);

namespace Jh\ImportTest\Asset;

use Jh\Import\Import\Importer;
use Jh\Import\Import\RequiresPreperation;

class CallablePrep implements RequiresPreperation
{
    public function __invoke()
    {

    }

    public function prepare(Importer $importer): void
    {
    }
}
