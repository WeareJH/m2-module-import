<?php

declare(strict_types=1);

namespace Jh\Import\Import;

interface RequiresPreperation
{
    public function prepare(Importer $importer): void;
}
