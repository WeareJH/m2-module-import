<?php

namespace Jh\Import\Archiver;

/**
 * @author Aydin Hassan <aydin@wearejh.com>
 */
class NullArchiver implements Archiver
{

    public function failed()
    {
        //noop
    }

    public function successful()
    {
        //noop
    }
}
