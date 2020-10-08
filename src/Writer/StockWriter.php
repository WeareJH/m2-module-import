<?php

declare(strict_types=1);

namespace Jh\Import\Writer;

use Jh\Import\Import\Record;
use Jh\Import\Import\Result;
use Jh\Import\Report\ReportItem;
use Jh\Import\Source\Source;
use Jh\Import\Config;
use Jh\Import\Writer\Utils\DisableEventObserver;
use Magento\CatalogInventory\Api\StockConfigurationInterface;
use Magento\CatalogInventory\Api\StockItemRepositoryInterface;
use Magento\CatalogInventory\Model\Spi\StockRegistryProviderInterface;
use Magento\CatalogInventory\Model\StockRegistryProvider;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;

class StockWriter implements Writer
{
    /**
     * @var StockRegistryProviderInterface
     */
    private $stockRegistryProvider;

    /**
     * @var StockRepositoryInterface
     */
    private $stockItemRepository;

    /**
     * @var StockConfigurationInterface
     */
    private $stockConfiguration;

    /**
     * @var AdapterInterface
     */
    private $adapter;

    /**
     * @var DisableEventObserver
     */
    private $disableEventObserver;

    /**
     * @var array
     */
    private $updatedIds = [];

    /**
     * @var array
     */
    private $skusToIds;

    public function __construct(
        StockRegistryProvider $stockRegistryProvider,
        StockItemRepositoryInterface $stockItemRepository,
        StockConfigurationInterface $stockConfiguration,
        ResourceConnection $connection,
        DisableEventObserver $disableEventObserver
    ) {
        $this->stockRegistryProvider = $stockRegistryProvider;
        $this->stockItemRepository = $stockItemRepository;
        $this->stockConfiguration = $stockConfiguration;
        $this->adapter = $connection->getConnection();
        $this->disableEventObserver = $disableEventObserver;

        $select = $this->adapter
            ->select()
            ->from('catalog_product_entity', ['sku', 'entity_id']);

        $this->skusToIds = $this->adapter->fetchPairs($select);
    }

    public function prepare(Source $source, Config $config)
    {
        $this->disableEventObserver->disable('clean_cache_by_tags', 'invalidate_varnish');

        $this->updatedIds = [];
    }

    public function write(Record $record, ReportItem $report)
    {
        $sku = $record->getColumnValue('sku');
        $qty = $record->getColumnValue('qty');


        if (!isset($this->skusToIds[$sku])) {
            $report->addError(sprintf('Product: "%s" does not exist.', $sku));
            return;
        }

        $stock = $this->stockRegistryProvider->getStockItem(
            $this->skusToIds[$sku],
            $this->stockConfiguration->getDefaultScopeId()
        );

        if (!$stock->getItemId()) {
            $stock->setProductId($this->skusToIds[$sku]);
        }

        $stock->setQty($qty);
        $stock->setIsInStock($qty ? true : false);
        $this->stockItemRepository->save($stock);
        $this->updatedIds[] = $this->skusToIds[$sku];
    }

    public function finish(Source $source): Result
    {
        return new Result(array_values($this->updatedIds));
    }
}
