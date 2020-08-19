<?php

namespace Jh\Import\Archiver;

use Jh\Import\Config;
use Jh\Import\Source\Csv;
use Jh\Import\Source\Source;
use Magento\Framework\ObjectManagerInterface;

/**
 * @author Aydin Hassan <aydin@wearejh.com>
 */
class Factory
{
    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var array
     */
    private static $sourceToArchiverMap = [
          Csv::class => CsvArchiver::class
    ];

    public function __construct(ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    public function getArchiverForSource(Source $source, Config $config): Archiver
    {
        foreach (self::$sourceToArchiverMap as $class => $archiver) {
            if (get_class($source) === $class || is_subclass_of($source, $class)) {
                return $this->objectManager->create(
                    $archiver,
                    [
                        'source' => $source,
                        'config' => $config
                    ]
                );
            }
        }

        return new NullArchiver();
    }
}
