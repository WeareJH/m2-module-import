<?php

namespace Jh\Import\Report\Handler;

use Jh\Import\Report\Message;
use Jh\Import\Report\Report;
use Jh\Import\Report\ReportItem;

/**
 * @author Aydin Hassan <aydin@hotmail.co.uk>
 */
class CollectingHandler implements Handler
{

    /**
     * @var array
     */
    public $messages = [];

    /**
     * @var array
     */
    public $itemMessages = [];

    public function start(Report $report, \DateTime $startTime): void
    {
        // noop
    }

    public function finish(Report $report, \DateTime $finishTime, int $memoryUsage): void
    {
        // noop
    }

    public function handleMessage(Message $message): void
    {
        $this->messages[] = $message->toArray();
    }

    public function handleItemMessage(ReportItem $item, Message $message): void
    {
        $this->itemMessages[] = $message->toArray();
    }
}
