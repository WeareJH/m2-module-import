<?php

namespace Jh\ImportTest\Controller\Adminhtml\Log;

use Jh\Import\Controller\Adminhtml\Log\Import;
use Jh\Import\Entity\ImportHistory;
use Jh\Import\Entity\ImportHistoryFactory;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\Message\Manager as MessageManager;
use Magento\Framework\App\Request\Http;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\ObjectManager\ObjectManager;
use Magento\Framework\Event\Manager as EventManager;
use Magento\Backend\Model\View\Result\RedirectFactory;
use Magento\Backend\Model\View\Result\Redirect;
use Prophecy\Prophecy\ObjectProphecy;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;

/**
 * @author Aydin Hassan <aydin@wearejh.com>
 */
class ImportTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var Context|ObjectProphecy
     */
    private $context;

    /**
     * @var PageFactory|ObjectProphecy
     */
    private $pageFactory;

    /**
     * @var MessageManager|ObjectProphecy
     */
    private $messageManager;

    /**
     * @var ResponseInterface|ObjectProphecy
     */
    private $response;

    /**
     * @var Http|ObjectProphecy
     */
    private $request;

    /**
     * @var ResultFactory|ObjectProphecy
     */
    private $resultFactory;

    /**
     * @var ObjectManager|ObjectProphecy
     */
    private $objectManager;

    /**
     * @var EventManager|ObjectProphecy
     */
    private $eventManager;

    /**
     * @var Redirect|ObjectProphecy
     */
    private $resultRedirect;

    /**
     * @var RedirectFactory|ObjectProphecy
     */
    private $resultRedirectFactory;

    /**
     * @var ImportHistoryFactory|ObjectProphecy
     */
    private $importHistoryFactory;

    /**
     * @var Import
     */
    private $import;

    public function setUp(): void
    {
        $this->context = $this->prophesize(Context::class);
        $this->pageFactory = $this->prophesize(PageFactory::class);
        $this->messageManager = $this->prophesize(MessageManager::class);
        $this->response = $this->prophesize(ResponseInterface::class);
        $this->request = $this->prophesize(Http::class);
        $this->resultFactory = $this->prophesize(ResultFactory::class);
        $this->objectManager = $this->prophesize(ObjectManager::class);
        $this->eventManager = $this->prophesize(EventManager::class);
        $this->resultRedirect = $this->prophesize(Redirect::class);
        $this->resultRedirectFactory = $this->prophesize(RedirectFactory::class);

        $this->resultRedirect->setPath('*/*/')->willReturn($this->resultRedirect->reveal());
        $this->resultRedirectFactory->create()->willReturn($this->resultRedirect->reveal());

        $this->context->getSession()->willReturn(false);
        $this->context->getCanUseBaseUrl()->willReturn(false);
        $this->context->getLocaleResolver()->willReturn(false);
        $this->context->getFormKeyValidator()->willReturn(false);
        $this->context->getBackendUrl()->willReturn(false);
        $this->context->getHelper()->willReturn(false);
        $this->context->getAuth()->willReturn(false);
        $this->context->getAuthorization()->willReturn(false);
        $this->context->getView()->willReturn(false);
        $this->context->getRedirect()->willReturn(false);
        $this->context->getActionFlag()->willReturn(false);
        $this->context->getUrl()->willReturn(false);
        $this->context->getEventManager()->willReturn($this->eventManager->reveal());
        $this->context->getObjectManager()->willReturn($this->objectManager->reveal());
        $this->context->getResultFactory()->willReturn($this->resultFactory->reveal());
        $this->context->getResultRedirectFactory()->willReturn($this->resultRedirectFactory->reveal());
        $this->context->getResponse()->willReturn($this->response->reveal());
        $this->context->getRequest()->willReturn($this->request->reveal());
        $this->context->getMessageManager()->willReturn($this->messageManager->reveal());

        $this->importHistoryFactory = $this->prophesize(ImportHistoryFactory::class);

        $this->import = new Import(
            $this->context->reveal(),
            $this->pageFactory->reveal(),
            $this->importHistoryFactory->reveal()
        );
    }

    public function testRedirectIsReturnedIfNoHistoryId(): void
    {
        $this->request->getParam('history_id')->willReturn(null);
        $redirect = $this->prophesize(\Magento\Framework\Controller\Result\Redirect::class);
        $redirect->setPath('*/*/index')->shouldBeCalled()->willReturn($redirect->reveal());
        $this->resultRedirectFactory->create()->willReturn($redirect->reveal());

        self::assertSame($redirect->reveal(), $this->import->execute());
    }

    public function testRedirectIsReturnedIfHistoryDoesNotExist(): void
    {
        $this->request->getParam('history_id')->willReturn(33);

        $history = $this->prophesize(ImportHistory::class);
        $history->load(33)->shouldBeCalled()->willReturn($history);
        $history->getId()->willReturn(null);

        $this->importHistoryFactory->create()->willReturn($history);

        $redirect = $this->prophesize(\Magento\Framework\Controller\Result\Redirect::class);
        $redirect->setPath('*/*/index')->shouldBeCalled()->willReturn($redirect->reveal());
        $this->resultRedirectFactory->create()->willReturn($redirect->reveal());

        self::assertSame($redirect->reveal(), $this->import->execute());
    }

    public function testWithExistingImportHistory(): void
    {
        $this->request->getParam('history_id')->willReturn(33);

        $history = $this->prophesize(ImportHistory::class);
        $history->load(33)->shouldBeCalled()->willReturn($history);
        $history->getId()->willReturn(33);
        $history->getData('import_name')->willReturn('product');
        $history->getStartedAt()->willReturn(new \DateTime());

        $this->importHistoryFactory->create()->willReturn($history);

        $page = $this->prophesize(\Magento\Framework\View\Result\Page::class);
        $config = $this->prophesize(\Magento\Framework\View\Page\Config::class);
        $title = $this->prophesize(\Magento\Framework\View\Page\Title::class);

        $page->getConfig()->willReturn($config->reveal());
        $config->getTitle()->willReturn($title->reveal());

        $this->pageFactory->create()->willReturn($page->reveal());

        self::assertSame($page->reveal(), $this->import->execute());
    }
}
