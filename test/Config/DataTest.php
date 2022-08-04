<?php

namespace Jh\ImportTest\Config;

use Jh\Import\Config\Data;
use Jh\Import\Config;
use Magento\Framework\Config\CacheInterface;
use Magento\Framework\Config\ReaderInterface;
use Magento\Framework\Serialize\Serializer\Serialize;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;

/**
 * @author Aydin Hassan <aydin@wearejh.com>
 */
class DataTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ReaderInterface
     */
    private $reader;

    /**
     * @var CacheInterface
     */
    private $cache;

    /**
     * @var array
     */
    private static $testData = [
        'product' => [
            'source' => 'Jh\Import\Source\Csv',
            'incoming_directory' => 'jh_import/incoming',
            'match_files' => 'rdrive.csv',
            'specification' => 'Jh\Import\Specification\Product',
            'writer' => 'Jh\Import\Writer\Product',
            'type' => 'files'
        ]
    ];

    public function setUp(): void
    {
        $this->reader = $this->prophesize(ReaderInterface::class);
        $this->cache = $this->prophesize(CacheInterface::class);
    }

    public function testCacheIsSavedIfNotCached(): void
    {
        $this->cache->load('cache-id')->willReturn(false)->shouldBeCalled();
        $this->reader->read()->willReturn(static::$testData)->shouldBeCalled();
        $this->cache->save(serialize(static::$testData), 'cache-id', [])->shouldBeCalled();

        new Data($this->reader->reveal(), $this->cache->reveal(), 'cache-id', new Serialize());
    }

    public function testDataIsRetrievedFromCacheIfExists(): void
    {
        $this->cache->load('cache-id')->willReturn(serialize(static::$testData))->shouldBeCalled();
        $this->reader->read()->shouldNotBeCalled();

        new Data($this->reader->reveal(), $this->cache->reveal(), 'cache-id', new Serialize());
    }

    public function testHasImportReturnsTrueIfImportExists(): void
    {
        $this->cache->load('cache-id')->willReturn(serialize(static::$testData))->shouldBeCalled();
        $this->reader->read()->shouldNotBeCalled();

        $config = new Data($this->reader->reveal(), $this->cache->reveal(), 'cache-id', new Serialize());
        self::assertTrue($config->hasImport('product'));
    }

    public function testHasImportReturnsFalseIfImportNotExists(): void
    {
        $this->cache->load('cache-id')->willReturn(serialize(static::$testData))->shouldBeCalled();
        $this->reader->read()->shouldNotBeCalled();

        $config = new Data($this->reader->reveal(), $this->cache->reveal(), 'cache-id', new Serialize());
        self::assertFalse($config->hasImport('stock'));
    }

    public function testGetImportConfigByName(): void
    {
        $this->cache->load('cache-id')->willReturn(serialize(static::$testData))->shouldBeCalled();
        $this->reader->read()->shouldNotBeCalled();

        $config = new Data($this->reader->reveal(), $this->cache->reveal(), 'cache-id', new Serialize());

        self::assertInstanceOf(Config::class, $config->getImportConfigByName('product'));
    }

    public function testGetImportConfigByNameReturnsNullIfImportNotExists(): void
    {
        $this->cache->load('cache-id')->willReturn(serialize(static::$testData))->shouldBeCalled();
        $this->reader->read()->shouldNotBeCalled();

        $config = new Data($this->reader->reveal(), $this->cache->reveal(), 'cache-id', new Serialize());
        self::assertNull($config->getImportConfigByName('stock'));
    }

    public function testGetImportType(): void
    {
        $this->cache->load('cache-id')->willReturn(serialize(static::$testData))->shouldBeCalled();
        $this->reader->read()->shouldNotBeCalled();

        $config = new Data($this->reader->reveal(), $this->cache->reveal(), 'cache-id', new Serialize());
        self::assertEquals('files', $config->getImportType('product'));
    }

    public function testGetImportTypeReturnsNullIfImportNotExists(): void
    {
        $this->cache->load('cache-id')->willReturn(serialize(static::$testData))->shouldBeCalled();
        $this->reader->read()->shouldNotBeCalled();

        $config = new Data($this->reader->reveal(), $this->cache->reveal(), 'cache-id', new Serialize());
        self::assertNull($config->getImportType('stock'));
    }

    public function testGetAllImportNames(): void
    {
        $this->cache->load('cache-id')->willReturn(serialize(static::$testData))->shouldBeCalled();
        $this->reader->read()->shouldNotBeCalled();

        $config = new Data($this->reader->reveal(), $this->cache->reveal(), 'cache-id', new Serialize());
        self::assertSame(
            ['product'],
            $config->getAllImportNames()
        );
    }
}
