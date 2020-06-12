<?php

namespace Jh\Import\Config;

use Jh\Import\Config;
use Magento\Framework\Config\Data as DataConfig;

/**
 * @author Aydin Hassan <aydin@wearejh.com>
 */
class Data extends DataConfig
{
    public function hasImport(string $name): bool
    {
        return null !== $this->get($name);
    }

    /**
     * @param string $importName
     * @return Config|null
     */
    public function getImportConfigByName(string $importName)
    {
        $importConfig = $this->get($importName);

        if (null === $importConfig) {
            return null;
        }

        return new Config($importName, $importConfig);
    }

    /**
     * @param string $importName
     * @return string|null
     */
    public function getImportType(string $importName)
    {
        return $this->get(sprintf('%s/type', $importName));
    }


    public function getAllImportNames(): array
    {
        return array_keys($this->get());
    }
}
