<?php

namespace Jh\Import\Controller\Adminhtml\Config;

use Jh\Import\Config\Data;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\PageFactory;
use Magento\Backend\Model\View\Result\Page;

/**
 * @author Aydin Hassan <aydin@wearejh.com>
 */
class Info extends Action
{
    /**
     * Authorization level
     *
     * @see _isAllowed()
     */
    const ADMIN_RESOURCE = 'Jh_Import::config';

    /**
     * @var PageFactory
     */
    private $resultPageFactory;

    /**
     * @var Data
     */
    private $config;

    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        Data $config
    ) {
        $this->resultPageFactory    = $resultPageFactory;
        $this->config               = $config;
        parent::__construct($context);
    }

    public function execute(): ResultInterface
    {
        if (!$this->getRequest()->getParam('name')) {
            return $this->resultRedirectFactory->create()->setPath('*/*/index');
        }

        $importName = $this->getRequest()->getParam('name');

        if (!$this->config->hasImport($importName)) {
            return $this->resultRedirectFactory->create()->setPath('*/*/index');
        }

        $page = $this->resultPageFactory->create();
        $page->getConfig()->getTitle()->prepend(__(sprintf('JH Import Info for import: "%s"', $importName)));
        return $page;
    }
}
