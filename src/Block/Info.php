<?php

namespace Jh\Import\Block;

use Jh\Import\Config;
use Jh\Import\Config\Data;
use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;

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

    /**
     * @var \Magento\Cron\Model\Config
     */
    private $cronConfig;

    public function __construct(
        Context $context,
        Data $config,
        \Magento\Cron\Model\Config $cronConfig
    ) {
        parent::__construct($context);

        $this->config = $config;
        $this->cronConfig = $cronConfig;
    }

    public function getImport() : Config
    {
        return $this->config->getImportConfigByName($this->getRequest()->getParam('name'));
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

    public function hasCron() : bool
    {
        $jobs = $this->cronConfig->getJobs();

        return $this->getImport()->hasCron()
            && isset($jobs['default'][$this->getImport()->getCron()]);
    }

    public function getCronExpression() : string
    {
        $jobs = $this->cronConfig->getJobs();

        if (!$this->getImport()->hasCron()) {
            throw new \RuntimeException('Import has no cron code set');
        }

        $cronCode = $this->getImport()->getCron();

        if (isset($jobs['default'][$cronCode])) {
            return $jobs['default'][$cronCode]['schedule'];
        }

        throw new \RuntimeException('Import\'s cron job does not exist');
    }
}
