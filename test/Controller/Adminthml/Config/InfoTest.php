<?php

namespace Jh\ImportTest\Controller\Adminhtml\Config;

use Jh\Import\Config\Data;
use Jh\Import\Controller\Adminhtml\Config\Index;
use Jh\Import\Controller\Adminhtml\Config\Info;
use Jh\UnitTestHelpers\ObjectHelper;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Prophecy\Prophecy\ObjectProphecy;
use PHPUnit\Framework\TestCase;
use Magento\Framework\Config\CacheInterface;
use Magento\Framework\Config\ReaderInterface;

/**
 * @author Aydin Hassan <aydin@wearejh.com>
 */
class InfoTest extends TestCase
{
    use ObjectHelper;

    /**
     * @var PageFactory|ObjectProphecy
     */
    private $pageFactory;

    /**
     * @var Index
     */
    private $controller;

    public function setUp()
    {
        $context = $this->getObject(Context::class);

        $this->pageFactory = $this->prophesize(PageFactory::class);

        $reader = $this->prophesize(ReaderInterface::class);
        $cache  = $this->prophesize(CacheInterface::class);

        $imports = [
            'product' => ['type' => 'files'],
            'stock' => ['type' => 'files']
        ];

        $cache->load('cache-id')->willReturn(serialize($imports))->shouldBeCalled();
        $data = new Data($reader->reveal(), $cache->reveal(), 'cache-id');

        $this->controller = new Info(
            $context,
            $this->pageFactory->reveal(),
            $data
        );
    }

    public function testRedirectIsReturnedIfNameParamNotPreset()
    {
        $this->retrieveChildMock(Context::class, 'request')
            ->getParam('name')
            ->willReturn(false);

        $redirect = $this->prophesize(\Magento\Framework\Controller\Result\Redirect::class);
        $redirect->setPath('*/*/index')->willReturn($redirect);

        $this->retrieveChildMock(Context::class, 'resultRedirectFactory')
            ->create()
            ->willReturn($redirect->reveal());

        self::assertSame($redirect->reveal(), $this->controller->execute());
    }

    public function testRedirectIsReturnedIfImportDoesNotExist()
    {
        $this->retrieveChildMock(Context::class, 'request')
            ->getParam('name')
            ->willReturn('image');

        $redirect = $this->prophesize(\Magento\Framework\Controller\Result\Redirect::class);
        $redirect->setPath('*/*/index')->willReturn($redirect);

        $this->retrieveChildMock(Context::class, 'resultRedirectFactory')
            ->create()
            ->willReturn($redirect->reveal());

        self::assertSame($redirect->reveal(), $this->controller->execute());
    }

    public function testPageIsReturnedIfImportExists()
    {
        $this->retrieveChildMock(Context::class, 'request')
            ->getParam('name')
            ->willReturn('product');

        $page = $this->prophesize(\Magento\Framework\View\Result\Page::class);
        $config = $this->prophesize(\Magento\Framework\View\Page\Config::class);
        $title = $this->prophesize(\Magento\Framework\View\Page\Title::class);

        $page->getConfig()->willReturn($config->reveal());
        $config->getTitle()->willReturn($title->reveal());

        $this->pageFactory->create()->willReturn($page->reveal());

        self::assertSame($page->reveal(), $this->controller->execute());
    }
}
