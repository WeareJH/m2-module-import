<?php

declare(strict_types=1);

namespace Jh\Import\Source;

use Jh\Import\Config;
use Magento\Framework\ObjectManagerInterface;

class SourceFactory
{
    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    public function __construct(ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    public function create(Config $config, array $args): Source
    {
        $source = $this->objectManager
            ->create($config->getSourceService(), $args);

        if (!$source instanceof Source) {
            throw new \RuntimeException(sprintf('Source must implement "%s"', Source::class));
        }

        return $source;
    }
}
