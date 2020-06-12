<?php

namespace Jh\Import\Import;

use Jh\Import\Source\Source;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;

/**
 * @author Aydin Hassan <aydin@wearejh.com>
 */
class History
{
    /**
     * @var AdapterInterface
     */
    private $dbAdapter;

    public function __construct(ResourceConnection $resourceConnection)
    {
        $this->dbAdapter = $resourceConnection->getConnection();
    }

    public function isImported(Source $source): bool
    {
        $select = $this->dbAdapter
            ->select()
            ->from('jh_import_history', 'COUNT(id)')
            ->where('source_id = ?', $source->getSourceId())
            ->where('finished IS NOT NULL');

        return $this->dbAdapter->fetchOne($select) > 0;
    }
}
