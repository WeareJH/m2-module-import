<?php

namespace Jh\Import\Report\Handler;

use Jh\Import\Report\Message;
use Jh\Import\Report\Report;
use Jh\Import\Report\ReportItem;
use Magento\Framework\App\ResourceConnection;

/**
 * @author Aydin Hassan <aydin@hotmail.co.uk>
 */
class DatabaseHandler implements Handler
{
    /**
     * @var \Magento\Framework\DB\Adapter\Pdo\Mysql
     */
    private $adapter;

    /**
     * @var int|null
     */
    private $id;

    public function __construct(ResourceConnection $resourceConnection)
    {
        $this->adapter = $resourceConnection->getConnection();
    }

    public function start(Report $report, \DateTime $startTime): void
    {
        $this->adapter->insert('jh_import_history', [
            'started'     => $startTime->format('Y-m-d H:i:s'),
            'import_name' => $report->getImportName(),
            'source_id'   => $report->getSourceId()
        ]);

        $this->id = $this->adapter->lastInsertId();
    }

    public function finish(Report $report, \DateTime $finishTime, int $memoryUsage): void
    {
        $this->adapter->update(
            'jh_import_history',
            [
                'finished'     => $finishTime->format('Y-m-d H:i:s'),
                'memory_usage' => $memoryUsage
            ],
            [
                'id = ?' => $this->id
            ]
        );
    }

    /**
     * TODO: Batch insert
     *
     * @param Message $message
     */
    public function handleMessage(Message $message): void
    {
        $this->adapter->insert('jh_import_history_log', [
            'history_id' => $this->id,
            'log_level'  => $message->getLogLevel(),
            'message'    => $message->getMessage(),
            'created'    => $message->getDateTime()->format('Y-m-d H:i:s')
        ]);
    }

    /**
     * TODO: Batch insert
     *
     * @param Message $message
     */
    public function handleItemMessage(ReportItem $item, Message $message): void
    {
        $this->adapter->insert('jh_import_history_item_log', [
            'history_id'     => $this->id,
            'log_level'      => $message->getLogLevel(),
            'reference_line' => $item->getReferenceLine(),
            'id_field'       => $item->getIdField(),
            'id_value'       => $item->getIdValue(),
            'created'        => $message->getDateTime()->format('Y-m-d H:i:s'),
            'message'        => $message->getMessage(),
        ]);
    }
}
