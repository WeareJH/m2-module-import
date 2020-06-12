<?php

namespace Jh\Import\Controller\Adminhtml\Config;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\View\Result\PageFactory;

/**
 * @author Aydin Hassan <aydin@wearejh.com>
 */
class Index extends Action
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

    public function __construct(Context $context, PageFactory $resultPageFactory)
    {
        $this->resultPageFactory = $resultPageFactory;
        parent::__construct($context);
    }

    public function execute(): ResultInterface
    {
        $page = $this->resultPageFactory->create();
        $page->getConfig()->getTitle()->prepend(__('JH Import Configuration'));
        return $page;
    }
}
