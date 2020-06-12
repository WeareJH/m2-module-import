<?php

namespace Jh\Import\Controller\Adminhtml\Files;

use Jh\Import\Config\Data;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Backend\Model\View\Result\Page;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Filesystem\Directory\WriteFactory;

/**
 * @author Aydin Hassan <aydin@hotmail.co.uk>
 */
class Download extends Action
{
    /**
     * Authorization level
     *
     * @see _isAllowed()
     */
    public const ADMIN_RESOURCE = 'Jh_Import::files';

    /**
     * @var FileFactory
     */
    private $fileFactory;

    /**
     * @var DirectoryList
     */
    private $directoryList;

    /**
     * @var WriteFactory
     */
    private $writeFactory;

    /**
     * @var Data
     */
    private $config;

    public function __construct(
        Context $context,
        FileFactory $fileFactory,
        DirectoryList $directoryList,
        WriteFactory $writeFactory,
        Data $config
    ) {
        $this->fileFactory = $fileFactory;
        $this->config = $config;
        $this->directoryList = $directoryList;
        $this->writeFactory = $writeFactory;
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

        $import = $this->config->getImportConfigByName($importName);
        $directoryWriter = $this->writeFactory->create(
            sprintf(
                '%s/%s',
                $this->directoryList->getPath(DirectoryList::VAR_DIR),
                $import->get($this->getRequest()->getParam('directory'))
            )
        );

        $fileContents = $directoryWriter->readFile($this->getRequest()->getParam('file'));

        return $this->fileFactory->create(
            $this->getRequest()->getParam('file'),
            $fileContents
        );
    }
}
