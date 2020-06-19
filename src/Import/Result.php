<?php

namespace Jh\Import\Import;

/**
 * @author Aydin Hassan <aydin@hotmail.co.uk>
 */
class Result
{
    /**
     * @var array
     */
    private $affectedIds;

    public function __construct(array $affectedIds)
    {
        $this->affectedIds = $affectedIds;
    }

    public function getAffectedIds(): array
    {
        return $this->affectedIds;
    }

    public function hasAffectedIds(): bool
    {
        return count($this->affectedIds) > 0;
    }

    public function affectedIdsCount(): int
    {
        return count($this->affectedIds);
    }
}
