<?php

namespace Jh\Import\AttributeProcessor;

use Jh\Import\Import\Record;
use Jh\Import\Report\ReportItem;
use Magento\Eav\Api\Data\AttributeInterface;

/**
 * @author Aydin Hassan <aydin@wearejh.com>
 */
interface IndividualAttributeProcessor
{
    /**
     * @param AttributeInterface $attribute
     * @param string $value
     * @param Record $record
     * @param ReportItem $report
     * @return int
     * @throws CouldNotCreateOptionException
     */
    public function process(AttributeInterface $attribute, string $value, Record $record, ReportItem $report);
}
