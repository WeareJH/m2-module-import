<?php

namespace Jh\Import\Ui\Component\Listing;

use Jh\Import\Config\Data;
use Magento\Framework\Api;
use Magento\Framework\Api\Search\AggregationInterface;
use Magento\Framework\Api\Search\DocumentInterface;
use Magento\Framework\Api\Search\SearchCriteriaInterface;
use Magento\Framework\Api\Search\SearchResultInterface;
use Magento\Framework\Data\Collection;
use Magento\Framework\View\Element\UiComponent\DataProvider\Document;

/**
 * @author Aydin Hassan <aydin@hotmail.co.uk>
 */
class ImportSearchResult extends Collection implements SearchResultInterface
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

    public function __construct(Collection\EntityFactoryInterface $entityFactory, Data $config)
    {
        parent::__construct($entityFactory);
        $this->setItemObjectClass(Document::class);

        $importNames = $config->getAllImportNames();

        foreach ($importNames as $importName) {
            $importConfig = $config->getImportConfigByName($importName);

            $item = $this->getNewEmptyItem();
            $item->setData($importConfig->all());
            $item->setData('name', $importName);

            $this->addItem($item);
        }
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
}
