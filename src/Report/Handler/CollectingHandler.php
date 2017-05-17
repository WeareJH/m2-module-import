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

    public function start(Report $report, \DateTime $startTime)
    {
        // noop
    }

    public function finish(Report $report, \DateTime $finishTime, int $memoryUsage)
    {
        // noop
    }

    public function handleMessage(Message $message)
    {
        $this->messages[] = $message->toArray();
    }

    public function handleItemMessage(ReportItem $item, Message $message)
    {
        $this->itemMessages[] = $message->toArray();
    }
}
