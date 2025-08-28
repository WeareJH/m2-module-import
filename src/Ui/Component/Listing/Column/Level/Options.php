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
        $levels = [
            'DEBUG' => Logger::DEBUG,
            'INFO' => Logger::INFO,
            'NOTICE' => Logger::NOTICE,
            'WARNING' => Logger::WARNING,
            'ERROR' => Logger::ERROR,
            'CRITICAL' => Logger::CRITICAL,
            'ALERT' => Logger::ALERT,
            'EMERGENCY' => Logger::EMERGENCY,
        ];

        return array_map(function ($levelName) {
            return [
                'value' => $levelName,
                'label' => $levelName,
            ];
        }, array_keys($levels));
    }
}
