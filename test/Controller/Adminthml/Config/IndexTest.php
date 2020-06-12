<?php

namespace Jh\ImportTest\Controller\Adminhtml\Config;

use Jh\Import\Controller\Adminhtml\Config\Index;
use Jh\UnitTestHelpers\ObjectHelper;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Prophecy\Prophecy\ObjectProphecy;
use PHPUnit\Framework\TestCase;

/**
 * @author Aydin Hassan <aydin@wearejh.com>
 */
class IndexTest extends TestCase
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

    public function setUp(): void
    {
        $context = $this->getObject(Context::class);

        $this->pageFactory = $this->prophesize(PageFactory::class);

        $this->controller = new Index(
            $context,
            $this->pageFactory->reveal()
        );
    }

    public function testIndex()
    {

        $page = $this->prophesize(\Magento\Framework\View\Result\Page::class);
        $config = $this->prophesize(\Magento\Framework\View\Page\Config::class);
        $title = $this->prophesize(\Magento\Framework\View\Page\Title::class);

        $page->getConfig()->willReturn($config->reveal());
        $config->getTitle()->willReturn($title->reveal());

        $this->pageFactory->create()->willReturn($page->reveal());

        self::assertSame($page->reveal(), $this->controller->execute());
    }
}
