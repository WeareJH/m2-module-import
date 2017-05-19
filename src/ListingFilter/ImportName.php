<?php

namespace Jh\Import\ListingFilter;

use Jh\Import\Config\Data;
use Magento\Framework\Data\OptionSourceInterface;

/**
 * @author Aydin Hassan <aydin@wearejh.com>
 */
class ImportName implements OptionSourceInterface
{
    /**
     * @var Data
     */
    private $importConfig;

    public function __construct(Data $importConfig)
    {
        $this->importConfig = $importConfig;
    }

    /**
     * Return array of options as value-label pairs
     *
     * @return array Format: array(array('value' => '<value>', 'label' => '<label>'), ...)
     */
    public function toOptionArray()
    {
        return array_map(function ($importName) {
            return [
                'value' => $importName,
                'label' => $importName
            ];
        }, $this->importConfig->getAllImportNames());
    }
}
