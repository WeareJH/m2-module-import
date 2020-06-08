<?php

namespace Jh\Import\Entity;

use Magento\Framework\Model\AbstractModel;

/**
 * @author Aydin Hassan <aydin@wearejh.com>
 */
class ImportHistory extends AbstractModel
{
    /**
     * Resource initialization
     * @coding
     */
    public function _construct() // @codingStandardsIgnoreLine
    {
        $this->_init(ImportHistoryResource::class);
    }

    public function getStartedAt()
    {
        return new \DateTime($this->getData('started'));
    }
}
