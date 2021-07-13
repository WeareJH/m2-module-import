<?php

namespace Jh\Import\Ui\Component\Listing;

use Jh\Import\Config\Data;
use Jh\Import\Entity\ImportHistory;
use Jh\Import\Entity\ImportHistoryResource;
use Jh\Import\Locker\Locker;
use Magento\Framework\Api;
use Magento\Framework\Api\Search\AggregationInterface;
use Magento\Framework\Api\Search\DocumentInterface;
use Magento\Framework\Api\Search\SearchCriteriaInterface;
use Magento\Framework\Api\Search\SearchResultInterface;
use Magento\Framework\Data\Collection\Db\FetchStrategyInterface;
use Magento\Framework\Data\Collection\EntityFactoryInterface;
use Magento\Framework\DataObject;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Magento\Framework\View\Element\UiComponent\DataProvider\Document;
use Psr\Log\LoggerInterface;

/**
 * @author Aydin Hassan <aydin@hotmail.co.uk>
 */
class ImportSearchResult extends AbstractCollection implements SearchResultInterface
{
    /**
     * @var AggregationInterface
     */
    private $aggregations;

    /**
     * @var SearchCriteriaInterface
     */
    private $searchCriteria;

    /**
     * @var int|null
     */
    private $totalCount;

    public function __construct(
        Data $config,
        Locker $locker,
        EntityFactoryInterface $entityFactory,
        LoggerInterface $logger,
        FetchStrategyInterface $fetchStrategy,
        ManagerInterface $eventManager,
        AdapterInterface $connection = null,
        AbstractDb $resource = null
    ) {
        parent::__construct($entityFactory, $logger, $fetchStrategy, $eventManager, $connection, $resource);
        $this->setItemObjectClass(Document::class);

        $importNames = $config->getAllImportNames();

        foreach ($importNames as $importName) {
            $importConfig = $config->getImportConfigByName($importName);

            $item = $this->getNewEmptyItem();
            $item->setData($importConfig->all());
            $item->setData('name', $importName);
            $item->setData('lock_status', $locker->locked($importName) ? 'Locked' : 'Not locked');

            $this->addItem($item);
        }
    }

    public function loadWithFilter($printQuery = false, $logQuery = false)
    {
        return $this;
    }

    /**
     * Set items list.
     *
     * @param DocumentInterface[] $items
     * @return $this
     */
    public function setItems(array $items = null): self
    {
        if (!$items) {
            return $this;
        }

        $this->_items = [];

        foreach ($items as $item) {
            $this->addItem($item);
        }

        $this->totalCount = null;

        return $this;
    }

    public function getAggregations(): AggregationInterface
    {
        return $this->aggregations;
    }

    /**
     * @param AggregationInterface $aggregations
     * @return $this
     */
    public function setAggregations($aggregations): self
    {
        $this->aggregations = $aggregations;
        return $this;
    }

    public function getSearchCriteria(): SearchCriteriaInterface
    {
        return $this->searchCriteria;
    }

    public function setSearchCriteria(Api\SearchCriteriaInterface $searchCriteria): self
    {
        $this->searchCriteria = $searchCriteria;
        return $this;
    }

    public function getTotalCount(): int
    {
        if (!$this->totalCount) {
            $this->totalCount = $this->getSize();
        }
        return $this->totalCount;
    }


    public function setTotalCount($totalCount): self
    {
        $this->totalCount = $totalCount;
        return $this;
    }

    protected function _construct()
    {
        $this->_init(ImportHistory::class, ImportHistoryResource::class);
    }
}
