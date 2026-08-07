<?php

namespace Jh\Import\Block;

use Jh\Import\Config;
use Jh\Import\Config\Data;
use Jh\Import\Locker\Locker;
use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Cron\Model\Config as CronConfig;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * @author Aydin Hassan <aydin@hotmail.co.uk>
 */
class Info extends Template
{
    /**
     * @var Data
     */
    private $config;

    /**
     * @var array
     */
    private $typeBlocks = [
        'files' => TypeFiles::class
    ];

    /** @var CronConfig $cronConfig */
    private CronConfig $cronConfig;

    /**
     * @var Locker
     */
    private $locker;

    public function __construct(
        Context $context,
        Data $config,
        CronConfig $cronConfig,
        Locker $locker,
        ScopeConfigInterface $scopeConfig
    ) {
        parent::__construct($context);

        $this->config = $config;
        $this->cronConfig = $cronConfig;
        $this->locker = $locker;
        $this->scopeConfig = $scopeConfig;
    }

    public function getImport(): Config
    {
        return $this->config->getImportConfigByName($this->getRequest()->getParam('name'));
    }

    public function getLockStatus(): string
    {
        return $this->locker->locked($this->getImport()->getImportName()) ? 'Locked' : 'Not locked';
    }

    protected function _prepareLayout() //@codingStandardsIgnoreLine
    {
        $importType = $this->getImport()->getType();

        $blockClass = $this->typeBlocks[$importType] ?? null;

        if (!$blockClass) {
            return $this;
        }

        $this->addChild('type-info', $blockClass);

        return $this;
    }

    public function hasCron(): bool
    {
        $jobs = $this->cronConfig->getJobs();

        return $this->getImport()->hasCron()
            && isset($jobs[$this->getImport()->getCronGroup()][$this->getImport()->getCron()]);
    }

    public function getCronExpression(): string
    {
        $jobs = $this->cronConfig->getJobs();

        if (!$this->getImport()->hasCron()) {
            throw new \RuntimeException('Import has no cron code set');
        }

        $cronCode = $this->getImport()->getCron();
        $group = $this->getImport()->getCronGroup();

        if (isset($jobs[$group][$cronCode])) {
            if (isset($jobs[$group][$cronCode]['schedule'])) {
                return $jobs[$this->getImport()->getCronGroup()][$cronCode]['schedule'];
            } else if (isset($jobs[$group][$cronCode]['config_path'])) {
                return $this->scopeConfig->getValue(
                    $jobs[$group][$cronCode]['config_path'],
                    ScopeInterface::SCOPE_STORE
                );
            }
        }

        throw new \RuntimeException('Import\'s cron job does not exist');
    }

    public function getCronGroup(): string
    {
        $jobs = $this->cronConfig->getJobs();

        if (!$this->getImport()->hasCron()) {
            throw new \RuntimeException('Import has no cron code set');
        }

        $cronCode = $this->getImport()->getCron();

        if (isset($jobs[$this->getImport()->getCronGroup()][$cronCode])) {
            return $this->getImport()->getCronGroup();
        }

        throw new \RuntimeException('Import\'s cron job does not exist');
    }

    public function getCronCode(): string
    {
        $jobs = $this->cronConfig->getJobs();

        if (!$this->getImport()->hasCron()) {
            throw new \RuntimeException('Import has no cron code set');
        }

        $cronCode = $this->getImport()->getCron();

        if (isset($jobs[$this->getImport()->getCronGroup()][$cronCode])) {
            return $cronCode;
        }

        throw new \RuntimeException('Import\'s cron job does not exist');
    }
}
