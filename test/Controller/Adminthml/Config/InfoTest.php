<?php

namespace Jh\ImportTest\Controller\Adminhtml\Config;

use Jh\Import\Config\Data;
use Jh\Import\Controller\Adminhtml\Config\Info;
use Jh\UnitTestHelpers\ObjectHelper;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Config\CacheInterface;
use Magento\Framework\Config\ReaderInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Serialize\Serializer\Serialize;
use Magento\Framework\View\Page\Config;
use Magento\Framework\View\Page\Title;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;

/**
 * @author Aydin Hassan <aydin@wearejh.com>
 */
class InfoTest extends TestCase
{
    use ObjectHelper;
    use ProphecyTrait;

    /**
     * @var PageFactory|ObjectProphecy
     */
    private $pageFactory;

    /**
     * @var Info
     */
    private $controller;

    public function setUp(): void
    {
        $context = $this->getObject(Context::class);

        $this->pageFactory = $this->prophesize(PageFactory::class);

        $reader = $this->prophesize(ReaderInterface::class);
        $cache = $this->prophesize(CacheInterface::class);

        $imports = [
            'product' => ['type' => 'files'],
            'stock' => ['type' => 'files']
        ];

        $cache->load('cache-id')->willReturn(serialize($imports))->shouldBeCalled();
        $data = new Data($reader->reveal(), $cache->reveal(), 'cache-id', new Serialize());

        $this->controller = new Info(
            $context,
            $this->pageFactory->reveal(),
            $data
        );
    }

    public function testRedirectIsReturnedIfNameParamNotPreset(): void
    {
        $this->retrieveChildMock(Context::class, 'request')
            ->getParam('name')
            ->willReturn(false);

        $redirect = $this->prophesize(Redirect::class);
        $redirect->setPath('*/*/index')->willReturn($redirect);

        $this->retrieveChildMock(Context::class, 'resultRedirectFactory')
            ->create()
            ->willReturn($redirect->reveal());

        self::assertSame($redirect->reveal(), $this->controller->execute());
    }

    public function testRedirectIsReturnedIfImportDoesNotExist(): void
    {
        $this->retrieveChildMock(Context::class, 'request')
            ->getParam('name')
            ->willReturn('image');

        $redirect = $this->prophesize(Redirect::class);
        $redirect->setPath('*/*/index')->willReturn($redirect);

        $this->retrieveChildMock(Context::class, 'resultRedirectFactory')
            ->create()
            ->willReturn($redirect->reveal());

        self::assertSame($redirect->reveal(), $this->controller->execute());
    }

    public function testPageIsReturnedIfImportExists(): void
    {
        $this->retrieveChildMock(Context::class, 'request')
            ->getParam('name')
            ->willReturn('product');

        $page = $this->prophesize(Page::class);
        $config = $this->prophesize(Config::class);
        $title = $this->prophesize(Title::class);

        $page->getConfig()->willReturn($config->reveal());
        $config->getTitle()->willReturn($title->reveal());

        $this->pageFactory->create()->willReturn($page->reveal());

        self::assertSame($page->reveal(), $this->controller->execute());
    }
}
