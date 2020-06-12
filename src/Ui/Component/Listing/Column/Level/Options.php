<?php

namespace Jh\Import\Ui\Component\Listing\Column\Level;

use Magento\Framework\Data\OptionSourceInterface;
use Monolog\Logger;

/**
 * @author Reka Szekely <reka@wearejh.com>
 */
class Options implements OptionSourceInterface
{
    /**
     * @var array
     */
    protected $options;

    public function toOptionArray(): array
    {
        return array_map(function ($levelName) {
            return [
                'value' => $levelName,
                'label' => $levelName,
            ];
        }, array_keys(Logger::getLevels()));
    }
}
