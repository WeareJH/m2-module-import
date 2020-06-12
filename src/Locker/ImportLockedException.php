<?php

namespace Jh\Import\Locker;

/**
 * @author Aydin Hassan <aydin@wearejh.com>
 */
class ImportLockedException extends \RuntimeException
{
    public static function fromName(string $name): self
    {
        return new static(sprintf('Import with name "%s" is locked.', $name));
    }
}
