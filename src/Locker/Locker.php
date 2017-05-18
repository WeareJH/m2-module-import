<?php

namespace Jh\Import\Locker;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;

/**
 * @author Aydin Hassan <aydin@wearejh.com>
 */
class Locker
{
    /**
     * @var AdapterInterface
     */
    private $dbAdapter;

    public function __construct(ResourceConnection $resourceConnection)
    {
        $this->dbAdapter = $resourceConnection->getConnection();
    }

    /**
     * Lock the particular import so no others of the same type can run.
     *
     * @param string $importName
     * @return void
     * @throws ImportLockedException
     */
    public function lock(string $importName)
    {
        $this->checkAlreadyLocked($importName);
        $this->dbAdapter->insert('jh_import_lock', ['import_name' => $importName]);
    }

    public function release(string $importName)
    {
        $this->dbAdapter->delete('jh_import_lock', ['import_name = ?' => $importName]);
    }

    /**
     * Check if a particular import is locked - eg it is already running
     *
     * @param string $importName
     * @return void
     */
    private function checkAlreadyLocked(string $importName)
    {
        $select = $this->dbAdapter->select()
            ->from('jh_import_lock')
            ->where('import_name = ?', $importName);

        if (count($this->dbAdapter->fetchAll($select)) > 0) {
            throw ImportLockedException::fromName($importName);
        }
    }
}
