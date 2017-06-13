<?php

namespace Jh\ImportTest\Block;

use Jh\Import\Block\Info;
use Jh\Import\Block\TypeFiles;
use Jh\Import\Config;
use Jh\Import\Config\Data;
use Jh\UnitTestHelpers\ObjectHelper;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\View\LayoutInterface;
use PHPUnit\Framework\TestCase;
use Magento\Framework\Config\CacheInterface;
use Magento\Framework\Config\ReaderInterface;

class InfoTest extends TestCase
{
    use ObjectHelper;

    public function testGetImportReturnsConfigFromRequest()
    {
        $reader = $this->prophesize(ReaderInterface::class);
        $cache  = $this->prophesize(CacheInterface::class);

        $cache->load('cache-id')->willReturn(serialize(['product' => ['type' => 'files']]))->shouldBeCalled();

        $request = $this->prophesize(RequestInterface::class);
        $request->getParam('name')->willReturn('product');
        $context = $this->getObject(Context::class, [
            'request' => $request->reveal()
        ]);
        $config  = new Data($reader->reveal(), $cache->reveal(), 'cache-id');

        $cron = $this->prophesize(\Magento\Cron\Model\Config::class);

        $block = new Info($context, $config, $cron->reveal());

        $importConfig = $block->getImport();

        self::assertInstanceOf(Config::class, $importConfig);
        self::assertEquals('files', $importConfig->getType());
        self::assertEquals('product', $importConfig->getImportName());
    }

    public function testPrepareLayoutAddsTypeBlock()
    {
        $reader = $this->prophesize(ReaderInterface::class);
        $cache  = $this->prophesize(CacheInterface::class);

        $cache->load('cache-id')->willReturn(serialize(['product' => ['type' => 'files']]))->shouldBeCalled();

        $childBlock = $this->getObject(TypeFiles::class);
        $childBlock->setNameInLayout('type-info');

        $layout = $this->prophesize(LayoutInterface::class);
        $layout->createBlock(TypeFiles::class, 'info.type-info', ['data' => []])->willReturn($childBlock);
        $layout->getChildName('info', 'type-info')->willReturn(false, 'type-info');
        $layout->setChild('info', 'type-info', 'type-info')->shouldBeCalled();
        $layout->getBlock('type-info')->willReturn($childBlock);

        $request = $this->prophesize(RequestInterface::class);
        $request->getParam('name')->willReturn('product');
        $context = $this->getObject(Context::class, [
            'request' => $request->reveal(),
            'layout'  => $layout->reveal()
        ]);
        $config  = new Data($reader->reveal(), $cache->reveal(), 'cache-id');

        $cron = $this->prophesize(\Magento\Cron\Model\Config::class);

        $block = new Info($context, $config, $cron->reveal());
        $block->setNameInLayout('info');

        $refMethod = (new \ReflectionObject($block))->getMethod('_prepareLayout');
        $refMethod->setAccessible(true);
        $refMethod->invoke($block);

        self::assertSame($childBlock, $block->getChildBlock('type-info'));
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Import has no cron code set
     */
    public function testGetCronExpressionThrowsExceptionIfNoCronCodeSet()
    {
        $reader = $this->prophesize(ReaderInterface::class);
        $cache  = $this->prophesize(CacheInterface::class);

        $cache
            ->load('cache-id')
            ->willReturn(serialize(['product' => ['type' => 'files']]))
            ->shouldBeCalled();

        $request = $this->prophesize(RequestInterface::class);
        $request->getParam('name')->willReturn('product');
        $context = $this->getObject(Context::class, [
            'request' => $request->reveal()
        ]);
        $config  = new Data($reader->reveal(), $cache->reveal(), 'cache-id');

        $cron = $this->prophesize(\Magento\Cron\Model\Config::class);
        $cron->getJobs()->willReturn(['default' => []]);

        $block = new Info($context, $config, $cron->reveal());
        $block->getCronExpression();
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Import's cron job does not exist
     */
    public function testGetCronExpressionThrowsExceptionIfCronDoesNotExist()
    {
        $reader = $this->prophesize(ReaderInterface::class);
        $cache  = $this->prophesize(CacheInterface::class);

        $cache
            ->load('cache-id')
            ->willReturn(serialize(['product' => ['type' => 'files', 'cron' => 'my-cron-code']]))
            ->shouldBeCalled();

        $request = $this->prophesize(RequestInterface::class);
        $request->getParam('name')->willReturn('product');
        $context = $this->getObject(Context::class, [
            'request' => $request->reveal()
        ]);
        $config  = new Data($reader->reveal(), $cache->reveal(), 'cache-id');

        $cron = $this->prophesize(\Magento\Cron\Model\Config::class);
        $cron->getJobs()->willReturn(['default' => []]);

        $block = new Info($context, $config, $cron->reveal());
        $block->getCronExpression();
    }

    public function testGetCronExpressionReturnsCronExpression()
    {
        $reader = $this->prophesize(ReaderInterface::class);
        $cache  = $this->prophesize(CacheInterface::class);

        $cache
            ->load('cache-id')
            ->willReturn(serialize(['product' => ['type' => 'files', 'cron' => 'my-cron-code']]))
            ->shouldBeCalled();

        $request = $this->prophesize(RequestInterface::class);
        $request->getParam('name')->willReturn('product');
        $context = $this->getObject(Context::class, [
            'request' => $request->reveal()
        ]);
        $config  = new Data($reader->reveal(), $cache->reveal(), 'cache-id');

        $cron = $this->prophesize(\Magento\Cron\Model\Config::class);
        $cron->getJobs()->willReturn(['default' => ['my-cron-code' => ['schedule' => '*']]]);

        $block = new Info($context, $config, $cron->reveal());

        self::assertEquals('*', $block->getCronExpression());
    }

    public function testHasCronReturnsFalseIfNoCronCodeSet()
    {
        $reader = $this->prophesize(ReaderInterface::class);
        $cache  = $this->prophesize(CacheInterface::class);

        $cache
            ->load('cache-id')
            ->willReturn(serialize(['product' => ['type' => 'files']]))
            ->shouldBeCalled();

        $request = $this->prophesize(RequestInterface::class);
        $request->getParam('name')->willReturn('product');
        $context = $this->getObject(Context::class, [
            'request' => $request->reveal()
        ]);
        $config  = new Data($reader->reveal(), $cache->reveal(), 'cache-id');

        $cron = $this->prophesize(\Magento\Cron\Model\Config::class);
        $cron->getJobs()->willReturn(['default' => []]);

        $block = new Info($context, $config, $cron->reveal());

        self::assertFalse($block->hasCron());
    }

    public function testHasCronReturnsFalseIfCronDoesNotExist()
    {
        $reader = $this->prophesize(ReaderInterface::class);
        $cache  = $this->prophesize(CacheInterface::class);

        $cache
            ->load('cache-id')
            ->willReturn(serialize(['product' => ['type' => 'files', 'cron' => 'my-cron-code']]))
            ->shouldBeCalled();

        $request = $this->prophesize(RequestInterface::class);
        $request->getParam('name')->willReturn('product');
        $context = $this->getObject(Context::class, [
            'request' => $request->reveal()
        ]);
        $config  = new Data($reader->reveal(), $cache->reveal(), 'cache-id');

        $cron = $this->prophesize(\Magento\Cron\Model\Config::class);
        $cron->getJobs()->willReturn(['default' => []]);

        $block = new Info($context, $config, $cron->reveal());

        self::assertFalse($block->hasCron());
    }

    public function testHasCronReturnsTrueIfCronIsSetAndExists()
    {
        $reader = $this->prophesize(ReaderInterface::class);
        $cache  = $this->prophesize(CacheInterface::class);

        $cache
            ->load('cache-id')
            ->willReturn(serialize(['product' => ['type' => 'files', 'cron' => 'my-cron-code']]))
            ->shouldBeCalled();

        $request = $this->prophesize(RequestInterface::class);
        $request->getParam('name')->willReturn('product');
        $context = $this->getObject(Context::class, [
            'request' => $request->reveal()
        ]);
        $config  = new Data($reader->reveal(), $cache->reveal(), 'cache-id');

        $cron = $this->prophesize(\Magento\Cron\Model\Config::class);
        $cron->getJobs()->willReturn(['default' => ['my-cron-code' => ['schedule' => '*']]]);

        $block = new Info($context, $config, $cron->reveal());

        self::assertTrue($block->hasCron());
    }
}
