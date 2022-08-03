<?php

namespace Jh\ImportTest\Block;

use Jh\Import\Block\Info;
use Jh\Import\Block\TypeFiles;
use Jh\Import\Config;
use Jh\Import\Config\Data;
use Jh\Import\Locker\Locker;
use Jh\UnitTestHelpers\ObjectHelper;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Config\CacheInterface;
use Magento\Framework\Config\ReaderInterface;
use Magento\Framework\Serialize\Serializer\Serialize;
use Magento\Framework\View\LayoutInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use RuntimeException;

class InfoTest extends TestCase
{
    use ObjectHelper;
    use ProphecyTrait;

    public function testGetImportReturnsConfigFromRequest(): void
    {
        $reader = $this->prophesize(ReaderInterface::class);
        $cache = $this->prophesize(CacheInterface::class);

        $cache->load('cache-id')->willReturn(serialize(['product' => ['type' => 'files']]))->shouldBeCalled();

        $request = $this->prophesize(RequestInterface::class);
        $request->getParam('name')->willReturn('product');
        $context = $this->getObject(Context::class, [
            'request' => $request->reveal()
        ]);
        $config = new Data($reader->reveal(), $cache->reveal(), 'cache-id', new Serialize());

        $cron = $this->prophesize(\Magento\Cron\Model\Config::class);
        $locker = $this->prophesize(Locker::class);

        $block = new Info($context, $config, $cron->reveal(), $locker->reveal());

        $importConfig = $block->getImport();

        self::assertInstanceOf(Config::class, $importConfig);
        self::assertEquals('files', $importConfig->getType());
        self::assertEquals('product', $importConfig->getImportName());
    }

    public function testPrepareLayoutAddsTypeBlock(): void
    {
        $reader = $this->prophesize(ReaderInterface::class);
        $cache = $this->prophesize(CacheInterface::class);

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
            'layout' => $layout->reveal()
        ]);
        $config = new Data($reader->reveal(), $cache->reveal(), 'cache-id', new Serialize());

        $cron = $this->prophesize(\Magento\Cron\Model\Config::class);
        $locker = $this->prophesize(Locker::class);

        $block = new Info($context, $config, $cron->reveal(), $locker->reveal());
        $block->setNameInLayout('info');

        $refMethod = (new \ReflectionObject($block))->getMethod('_prepareLayout');
        $refMethod->setAccessible(true);
        $refMethod->invoke($block);

        self::assertSame($childBlock, $block->getChildBlock('type-info'));
    }

    public function testGetCronExpressionThrowsExceptionIfNoCronCodeSet(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Import has no cron code set');

        $reader = $this->prophesize(ReaderInterface::class);
        $cache = $this->prophesize(CacheInterface::class);

        $cache
            ->load('cache-id')
            ->willReturn(serialize(['product' => ['type' => 'files']]))
            ->shouldBeCalled();

        $request = $this->prophesize(RequestInterface::class);
        $request->getParam('name')->willReturn('product');
        $context = $this->getObject(Context::class, [
            'request' => $request->reveal()
        ]);
        $config = new Data($reader->reveal(), $cache->reveal(), 'cache-id', new Serialize());

        $cron = $this->prophesize(\Magento\Cron\Model\Config::class);
        $cron->getJobs()->willReturn(['default' => []]);

        $locker = $this->prophesize(Locker::class);

        $block = new Info($context, $config, $cron->reveal(), $locker->reveal());
        $block->getCronExpression();
    }

    public function testGetCronExpressionThrowsExceptionIfCronDoesNotExist(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Import's cron job does not exist");

        $reader = $this->prophesize(ReaderInterface::class);
        $cache = $this->prophesize(CacheInterface::class);

        $cache->load('cache-id')
            ->willReturn(serialize(['product' => ['type' => 'files', 'cron' => 'my-cron-code']]))
            ->shouldBeCalled();

        $request = $this->prophesize(RequestInterface::class);
        $request->getParam('name')->willReturn('product');
        $context = $this->getObject(Context::class, [
            'request' => $request->reveal()
        ]);
        $config = new Data($reader->reveal(), $cache->reveal(), 'cache-id', new Serialize());

        $cron = $this->prophesize(\Magento\Cron\Model\Config::class);
        $cron->getJobs()->willReturn(['default' => []]);

        $locker = $this->prophesize(Locker::class);

        $block = new Info($context, $config, $cron->reveal(), $locker->reveal());
        $block->getCronExpression();
    }

    public function testGetCronExpressionReturnsCronExpression(): void
    {
        $reader = $this->prophesize(ReaderInterface::class);
        $cache = $this->prophesize(CacheInterface::class);

        $cache->load('cache-id')
            ->willReturn(serialize(
                ['product' => ['type' => 'files', 'cron' => 'my-cron-code', 'cron_group' => 'default']]
            ))
            ->shouldBeCalled();

        $request = $this->prophesize(RequestInterface::class);
        $request->getParam('name')->willReturn('product');
        $context = $this->getObject(Context::class, [
            'request' => $request->reveal()
        ]);
        $config = new Data($reader->reveal(), $cache->reveal(), 'cache-id', new Serialize());

        $cron = $this->prophesize(\Magento\Cron\Model\Config::class);
        $cron->getJobs()->willReturn(['default' => ['my-cron-code' => ['schedule' => '*']]]);

        $locker = $this->prophesize(Locker::class);

        $block = new Info($context, $config, $cron->reveal(), $locker->reveal());

        self::assertEquals('*', $block->getCronExpression());
    }

    public function testHasCronReturnsFalseIfNoCronCodeSet(): void
    {
        $reader = $this->prophesize(ReaderInterface::class);
        $cache = $this->prophesize(CacheInterface::class);

        $cache->load('cache-id')
            ->willReturn(serialize(['product' => ['type' => 'files']]))
            ->shouldBeCalled();

        $request = $this->prophesize(RequestInterface::class);
        $request->getParam('name')->willReturn('product');
        $context = $this->getObject(Context::class, [
            'request' => $request->reveal()
        ]);
        $config = new Data($reader->reveal(), $cache->reveal(), 'cache-id', new Serialize());

        $cron = $this->prophesize(\Magento\Cron\Model\Config::class);
        $cron->getJobs()->willReturn(['default' => []]);

        $locker = $this->prophesize(Locker::class);

        $block = new Info($context, $config, $cron->reveal(), $locker->reveal());

        self::assertFalse($block->hasCron());
    }

    public function testHasCronReturnsFalseIfCronDoesNotExist(): void
    {
        $reader = $this->prophesize(ReaderInterface::class);
        $cache = $this->prophesize(CacheInterface::class);

        $cache->load('cache-id')
            ->willReturn(serialize(['product' => ['type' => 'files', 'cron' => 'my-cron-code']]))
            ->shouldBeCalled();

        $request = $this->prophesize(RequestInterface::class);
        $request->getParam('name')->willReturn('product');
        $context = $this->getObject(Context::class, [
            'request' => $request->reveal()
        ]);
        $config = new Data($reader->reveal(), $cache->reveal(), 'cache-id', new Serialize());

        $cron = $this->prophesize(\Magento\Cron\Model\Config::class);
        $cron->getJobs()->willReturn(['default' => []]);

        $locker = $this->prophesize(Locker::class);

        $block = new Info($context, $config, $cron->reveal(), $locker->reveal());

        self::assertFalse($block->hasCron());
    }

    public function testHasCronReturnsTrueIfCronIsSetAndExists(): void
    {
        $reader = $this->prophesize(ReaderInterface::class);
        $cache = $this->prophesize(CacheInterface::class);

        $cache->load('cache-id')
            ->willReturn(serialize(
                ['product' => ['type' => 'files', 'cron' => 'my-cron-code', 'cron_group' => 'default']]
            ))
            ->shouldBeCalled();

        $request = $this->prophesize(RequestInterface::class);
        $request->getParam('name')->willReturn('product');
        $context = $this->getObject(Context::class, [
            'request' => $request->reveal()
        ]);
        $config = new Data($reader->reveal(), $cache->reveal(), 'cache-id', new Serialize());

        $cron = $this->prophesize(\Magento\Cron\Model\Config::class);
        $cron->getJobs()->willReturn(['default' => ['my-cron-code' => ['schedule' => '*']]]);

        $locker = $this->prophesize(Locker::class);

        $block = new Info($context, $config, $cron->reveal(), $locker->reveal());

        self::assertTrue($block->hasCron());
    }

    public function testGetCronGroupThrowsExceptionIfNoCronCodeSet(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Import has no cron code set');

        $reader = $this->prophesize(ReaderInterface::class);
        $cache = $this->prophesize(CacheInterface::class);

        $cache->load('cache-id')
            ->willReturn(serialize(['product' => ['type' => 'files']]))
            ->shouldBeCalled();

        $request = $this->prophesize(RequestInterface::class);
        $request->getParam('name')->willReturn('product');
        $context = $this->getObject(Context::class, [
            'request' => $request->reveal()
        ]);
        $config = new Data($reader->reveal(), $cache->reveal(), 'cache-id', new Serialize());

        $cron = $this->prophesize(\Magento\Cron\Model\Config::class);
        $cron->getJobs()->willReturn(['default' => []]);

        $locker = $this->prophesize(Locker::class);

        $block = new Info($context, $config, $cron->reveal(), $locker->reveal());
        $block->getCronGroup();
    }

    public function testGetCronGroupThrowsExceptionIfCronDoesNotExist(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Import's cron job does not exist");

        $reader = $this->prophesize(ReaderInterface::class);
        $cache = $this->prophesize(CacheInterface::class);

        $cache->load('cache-id')
            ->willReturn(serialize(['product' => ['type' => 'files', 'cron' => 'my-cron-code']]))
            ->shouldBeCalled();

        $request = $this->prophesize(RequestInterface::class);
        $request->getParam('name')->willReturn('product');
        $context = $this->getObject(Context::class, [
            'request' => $request->reveal()
        ]);
        $config = new Data($reader->reveal(), $cache->reveal(), 'cache-id', new Serialize());

        $cron = $this->prophesize(\Magento\Cron\Model\Config::class);
        $cron->getJobs()->willReturn(['default' => []]);

        $locker = $this->prophesize(Locker::class);

        $block = new Info($context, $config, $cron->reveal(), $locker->reveal());
        $block->getCronGroup();
    }

    public function testGetCronGroupReturnsCronGroup(): void
    {
        $reader = $this->prophesize(ReaderInterface::class);
        $cache = $this->prophesize(CacheInterface::class);

        $cache->load('cache-id')
            ->willReturn(serialize(
                ['product' => ['type' => 'files', 'cron' => 'my-cron-code', 'cron_group' => 'default']]
            ))
            ->shouldBeCalled();

        $request = $this->prophesize(RequestInterface::class);
        $request->getParam('name')->willReturn('product');
        $context = $this->getObject(Context::class, [
            'request' => $request->reveal()
        ]);
        $config = new Data($reader->reveal(), $cache->reveal(), 'cache-id', new Serialize());

        $cron = $this->prophesize(\Magento\Cron\Model\Config::class);
        $cron->getJobs()->willReturn(['default' => ['my-cron-code' => ['schedule' => '*']]]);

        $locker = $this->prophesize(Locker::class);

        $block = new Info($context, $config, $cron->reveal(), $locker->reveal());

        self::assertEquals('default', $block->getCronGroup());
    }

    public function testGetCronCodeThrowsExceptionIfNoCronCodeSet(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Import has no cron code set');


        $reader = $this->prophesize(ReaderInterface::class);
        $cache = $this->prophesize(CacheInterface::class);

        $cache->load('cache-id')
            ->willReturn(serialize(['product' => ['type' => 'files']]))
            ->shouldBeCalled();

        $request = $this->prophesize(RequestInterface::class);
        $request->getParam('name')->willReturn('product');
        $context = $this->getObject(Context::class, [
            'request' => $request->reveal()
        ]);
        $config = new Data($reader->reveal(), $cache->reveal(), 'cache-id', new Serialize());

        $cron = $this->prophesize(\Magento\Cron\Model\Config::class);
        $cron->getJobs()->willReturn(['default' => []]);

        $locker = $this->prophesize(Locker::class);

        $block = new Info($context, $config, $cron->reveal(), $locker->reveal());
        $block->getCronCode();
    }

    public function testGetCronCodeThrowsExceptionIfCronDoesNotExist(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Import's cron job does not exist");

        $reader = $this->prophesize(ReaderInterface::class);
        $cache = $this->prophesize(CacheInterface::class);

        $cache->load('cache-id')
            ->willReturn(serialize(['product' => ['type' => 'files', 'cron' => 'my-cron-code']]))
            ->shouldBeCalled();

        $request = $this->prophesize(RequestInterface::class);
        $request->getParam('name')->willReturn('product');
        $context = $this->getObject(Context::class, [
            'request' => $request->reveal()
        ]);
        $config = new Data($reader->reveal(), $cache->reveal(), 'cache-id', new Serialize());

        $cron = $this->prophesize(\Magento\Cron\Model\Config::class);
        $cron->getJobs()->willReturn(['default' => []]);

        $locker = $this->prophesize(Locker::class);

        $block = new Info($context, $config, $cron->reveal(), $locker->reveal());
        $block->getCronCode();
    }

    public function testGetCronCodeReturnsCronCode(): void
    {
        $reader = $this->prophesize(ReaderInterface::class);
        $cache = $this->prophesize(CacheInterface::class);

        $cache->load('cache-id')
            ->willReturn(serialize(
                ['product' => ['type' => 'files', 'cron' => 'my-cron-code', 'cron_group' => 'default']]
            ))
            ->shouldBeCalled();

        $request = $this->prophesize(RequestInterface::class);
        $request->getParam('name')->willReturn('product');
        $context = $this->getObject(Context::class, [
            'request' => $request->reveal()
        ]);
        $config = new Data($reader->reveal(), $cache->reveal(), 'cache-id', new Serialize());

        $cron = $this->prophesize(\Magento\Cron\Model\Config::class);
        $cron->getJobs()->willReturn(['default' => ['my-cron-code' => ['schedule' => '*']]]);

        $locker = $this->prophesize(Locker::class);

        $block = new Info($context, $config, $cron->reveal(), $locker->reveal());

        self::assertEquals('my-cron-code', $block->getCronCode());
    }

    public function testGetLockStatusWhenImportLocked(): void
    {
        $reader = $this->prophesize(ReaderInterface::class);
        $cache = $this->prophesize(CacheInterface::class);

        $cache->load('cache-id')
            ->willReturn(serialize(['product' => ['type' => 'files', 'cron' => 'my-cron-code']]))
            ->shouldBeCalled();

        $request = $this->prophesize(RequestInterface::class);
        $request->getParam('name')->willReturn('product');
        $context = $this->getObject(Context::class, [
            'request' => $request->reveal()
        ]);
        $config = new Data($reader->reveal(), $cache->reveal(), 'cache-id', new Serialize());

        $cron = $this->prophesize(\Magento\Cron\Model\Config::class);
        $cron->getJobs()->willReturn(['default' => []]);

        $locker = $this->prophesize(Locker::class);
        $locker->locked('product')->willReturn(true);

        $block = new Info($context, $config, $cron->reveal(), $locker->reveal());

        self::assertEquals('Locked', $block->getLockStatus());
    }

    public function testGetLockStatusWhenImportNotLocked(): void
    {
        $reader = $this->prophesize(ReaderInterface::class);
        $cache = $this->prophesize(CacheInterface::class);

        $cache->load('cache-id')
            ->willReturn(serialize(['product' => ['type' => 'files', 'cron' => 'my-cron-code']]))
            ->shouldBeCalled();

        $request = $this->prophesize(RequestInterface::class);
        $request->getParam('name')->willReturn('product');
        $context = $this->getObject(Context::class, [
            'request' => $request->reveal()
        ]);
        $config = new Data($reader->reveal(), $cache->reveal(), 'cache-id', new Serialize());

        $cron = $this->prophesize(\Magento\Cron\Model\Config::class);
        $cron->getJobs()->willReturn(['default' => []]);

        $locker = $this->prophesize(Locker::class);
        $locker->locked('product')->willReturn(false);

        $block = new Info($context, $config, $cron->reveal(), $locker->reveal());

        self::assertEquals('Not locked', $block->getLockStatus());
    }
}
