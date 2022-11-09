<?php

declare(strict_types=1);

namespace Jh\Import\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;

class AppConfigProvider
{
    private const CONFIG_PATH_PREFIX = 'jh_import/default';

    protected ScopeConfigInterface $scopeConfig;

    public function __construct(ScopeConfigInterface $scopeConfig)
    {
        $this->scopeConfig = $scopeConfig;
    }

    public function getImportTypeOptionDefaultValue(string $importType, string $option)
    {
        $value = $this->scopeConfig->getValue(
            sprintf('%s/%s/%s', self::CONFIG_PATH_PREFIX, $importType, $option),
            ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
            null
        );

        return $value ?: null;
    }
}
