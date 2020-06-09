<?php

namespace Jh\Import\Entity;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Framework\Model\ResourceModel\Db\Context;

/**
 * @author Aydin Hassan <aydin@wearejh.com>
 */
class ImportHistoryResource extends AbstractDb
{
    /**
     * @var ImportHistoryFactory
     */
    private $importHistoryFactory;

    public function __construct(ImportHistoryFactory $importHistoryFactory, Context $context, $connectionName = null)
    {
        $this->importHistoryFactory = $importHistoryFactory;
        parent::__construct($context, $connectionName);
    }

    /**
     * Resource initialization
     */
    public function _construct() // @codingStandardsIgnoreLine
    {
        $this->_init('jh_import_history', 'id');
    }

    public function getLastImportByName(string $importName) : ImportHistory
    {
        $model = $this->importHistoryFactory->create();

        $select = $this->getConnection()
            ->select()
            ->from($this->getMainTable())
            ->where('import_name', $importName)
            ->order('finished')
            ->limitPage(1, 1);

        $row = $this->getConnection()->fetchRow($select);

        if ($row) {
            $model->setData($row);
        }

        return $model;
    }
}
