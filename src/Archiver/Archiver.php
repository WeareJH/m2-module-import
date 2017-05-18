<?php

namespace Jh\Import\Archiver;

/**
 * @author Aydin Hassan <aydin@wearejh.com>
 */
interface Archiver
{
    public function failed();

    public function successful();
}
