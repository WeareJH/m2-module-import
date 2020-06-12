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
        $class = get_class($source);

        if (!isset(self::$sourceToArchiverMap[$class])) {
            return new NullArchiver();
        }

        return $this->objectManager->create(
            self::$sourceToArchiverMap[$class],
            [
                'source' => $source,
                'config' => $config
            ]
        );
    }
}
