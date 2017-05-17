<?php

namespace Jh\Import\Report\Handler;

use Jh\Import\Report\Message;
use Jh\Import\Report\Report;
use Jh\Import\Report\ReportItem;

/**
 * @author Aydin Hassan <aydin@hotmail.co.uk>
 */
interface Handler
{
    public function start(Report $report, \DateTime $startTime);

    public function finish(Report $report, \DateTime $finishTime, int $memoryUsage);

    public function handleMessage(Message $message);

    public function handleItemMessage(ReportItem $item, Message $message);
}
