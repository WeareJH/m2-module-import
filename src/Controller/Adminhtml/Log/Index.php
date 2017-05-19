<?php

namespace Jh\Import\Controller\Adminhtml\Log;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\PageFactory;
use Magento\Backend\Model\View\Result\Page;
use Magento\Framework\Exception\NotFoundException;

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
    const ADMIN_RESOURCE = 'Jh_Import::logs';

    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    /**
     * @var Registry
     */
    protected $coreRegistry;

    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        Registry $coreRegistry
    ) {
        $this->resultPageFactory    = $resultPageFactory;
        $this->coreRegistry         = $coreRegistry;
        parent::__construct($context);
    }

    /**
     * @throws NotFoundException
     */
    public function execute() : Page
    {
        $page = $this->resultPageFactory->create();
        $page->getConfig()->getTitle()->prepend(__('JH Import Log'));
        return $page;
    }
}
