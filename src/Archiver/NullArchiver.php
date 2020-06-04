<?php

namespace Jh\Import\Archiver;

/**
 * @author Aydin Hassan <aydin@wearejh.com>
 */
class NullArchiver implements Archiver
{

    public function failed() : void
    {
        //noop
    }

    public function successful() : void
    {
        //noop
    }
}
