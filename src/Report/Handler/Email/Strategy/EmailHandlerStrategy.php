<?php

declare(strict_types=1);

namespace Jh\Import\Report\Handler\Email\Strategy;

use DateTime;
use Jh\Import\Report\Message;
use Jh\Import\Report\Report;

interface EmailHandlerStrategy
{
    /**
     * @param array[ReportItem, Message]
     * @return array[ReportItem, Message]
     */
    public function filterItemMessages(array $messages): array;

    /**
     * @param Message[] $messages
     * @return Message[]
     */
    public function filterImportMessages(array $messages): array;

    public function renderInfo(Report $report, DateTime $startTime, DateTime $finishTime, int $memoryUsage): string;

    /**
     * @param array[ReportItem, Message]
     * @return string
     */
    public function renderItemMessages(array $messages): string;

    /**
     * @param Message[]
     * @return string
     */
    public function renderImportMessages(array $messages): string;
}
