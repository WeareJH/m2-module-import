<?php

namespace Jh\Import\Report\Handler;

use Jh\Import\Report\Message;
use Jh\Import\Report\Report;
use Jh\Import\Report\ReportItem;

/**
 * @author Aydin Hassan <aydin@hotmail.co.uk>
 */
class BufferHandler implements Handler
{

    private $messages = [];
    private $itemMessages = [];



    public function start(Report $report, \DateTime $startTime)
    {
        // TODO: Implement start() method.
    }

    public function finish(Report $report, \DateTime $finishTime, int $memoryUsage)
    {
        // TODO: Implement finish() method.
    }

    public function handleMessage(Message $message)
    {
        $this->messages[] = $message;
    }

    public function handleItemMessage(ReportItem $item, Message $message)
    {
        // TODO: Implement handleItemMessage() method.
    }
}
