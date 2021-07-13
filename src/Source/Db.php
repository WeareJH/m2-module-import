<?php
declare(strict_types=1);

namespace Jh\Import\Source;

use Countable;
use Jh\Import\Report\Report;
use Magento\Framework\App\ResourceConnection;
use PDO;
use Zend_Exception;

/**
 * @author Maciej Sławik <maciej@wearejh.com>
 */
class Db implements Source, Countable
{
    private ResourceConnection $resourceConnection;
    private string $connectionName;
    private string $sourceId;
    private string $selectSql;
    private string $countSql;
    private string $idField;

    public function __construct(
        ResourceConnection $resourceConnection,
        string $connectionName,
        string $sourceId,
        string $selectSql,
        string $countSql,
        string $idField
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->connectionName = $connectionName;
        $this->sourceId = $sourceId . time();
        $this->selectSql = $selectSql;
        $this->countSql = $countSql;
        $this->idField = $idField;
    }

    public function traverse(callable $onSuccess, callable $onError, Report $report): void
    {
        try {
            foreach ($this->iterateSet() as $row) {
                $onSuccess($row[$this->idField], $row);
            }
        } catch (Zend_Exception $e) {
            $report->addError($e->getMessage());
            $onError(null);
        }
    }

    public function getSourceId(): string
    {
        return $this->sourceId;
    }

    public function count()
    {
        $connection = $this->resourceConnection->getConnection($this->connectionName);
        $count = $connection->fetchOne($this->countSql);
        return (int) $count;
    }

    private function iterateSet()
    {
        $connection = $this->resourceConnection->getConnection($this->connectionName);
        $query = $connection->query($this->selectSql);
        while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
            yield $row;
        }
    }
}
