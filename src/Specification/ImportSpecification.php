<?php

namespace Jh\Import\Specification;

use Jh\Import\Import\Importer;

/**
 * @author Aydin Hassan <aydin@wearejh.com>
 */
interface ImportSpecification
{
    public function configure(Importer $import);
}
