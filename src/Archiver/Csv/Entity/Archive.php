<?php

declare(strict_types=1);

namespace Jh\Import\Archiver\Csv\Entity;

use Magento\Framework\Model\AbstractModel;

class Archive extends AbstractModel
{
    public function _construct() // @codingStandardsIgnoreLine
    {
        $this->_init(ArchiveResource::class);
    }

    public function isFileDeleted(): bool
    {
        return (bool) $this->getData('deleted');
    }

    public function isFileArchived(): bool
    {
        return (bool) $this->getData('archived');
    }

    public function isFileAvailable(): bool
    {
        return !$this->isFileDeleted() && !$this->isFileArchived();
    }
}
