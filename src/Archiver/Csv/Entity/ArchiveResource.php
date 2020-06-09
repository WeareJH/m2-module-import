<?php

declare(strict_types=1);

namespace Jh\Import\Archiver\Csv\Entity;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Framework\Model\ResourceModel\Db\Context;

class ArchiveResource extends AbstractDb
{
    /**
     * @var ArchiveFactory
     */
    private $archiveFactory;

    public function __construct(ArchiveFactory $archiveFactory, Context $context, $connectionName = null)
    {
        $this->archiveFactory = $archiveFactory;
        parent::__construct($context, $connectionName);
    }

    protected function _construct() // @codingStandardsIgnoreLine
    {
        $this->_init('jh_import_archive_csv', 'id');
    }

    public function getBySourceId(string $sourceId) : Archive
    {
        $archive = $this->archiveFactory->create();

        $this->load($archive, $sourceId, 'source_id');

        return $archive;
    }
}
