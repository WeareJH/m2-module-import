<?php

namespace Jh\Import\Controller\Adminhtml\Log;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\View\Result\PageFactory;
use Jh\Import\Entity\ImportHistoryFactory;

/**
 * @author Aydin Hassan <aydin@wearejh.com>
 */
class Import extends Action
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
    private $resultPageFactory;

    /**
     * @var ImportHistoryFactory
     */
    private $importHistoryFactory;


    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        ImportHistoryFactory $importHistoryFactory
    ) {
        $this->resultPageFactory    = $resultPageFactory;
        $this->importHistoryFactory = $importHistoryFactory;
        parent::__construct($context);
    }

    public function execute(): ResultInterface
    {
        if (!$this->getRequest()->getParam('history_id')) {
            return $this->resultRedirectFactory->create()->setPath('*/*/index');
        }

        $import = $this->importHistoryFactory->create()->load(
            $this->getRequest()->getParam('history_id')
        );

        if (!$import->getId()) {
            return $this->resultRedirectFactory->create()->setPath('*/*/index');
        }

        $title = sprintf(
            'Import log for: "%s" started on %s',
            $import->getData('import_name'),
            $import->getStartedAt()->format('j F, Y H:i:s')
        );

        $page = $this->resultPageFactory->create();
        $page->getConfig()->getTitle()->prepend($title);
        return $page;
    }
}
