<?php

declare(strict_types=1);

namespace Jh\ImportTest\Source;

use ArrayIterator;
use Jh\Import\Config;
use Jh\Import\Source\Iterator;
use Jh\Import\Source\Source;
use Jh\Import\Source\SourceFactory;
use Magento\Framework\ObjectManagerInterface;
use PHPUnit\Framework\TestCase;

class SourceFactoryTest extends TestCase
{
    public function testCreateThrowsExceptionIfSourceDoesNotImplementCorrectInterface() : void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(sprintf('Source must implement "%s"', Source::class));

        $om = $this->prophesize(ObjectManagerInterface::class);
        $om->create('stdClass', [])->willReturn(new \stdClass);

        $conf = new Config('my-import', ['source' => 'stdClass']);

        (new SourceFactory($om->reveal()))->create($conf, []);
    }

    public function testCreateWithValidSource() : void
    {
        $om = $this->prophesize(ObjectManagerInterface::class);
        $source = new Iterator(new ArrayIterator([]));
        $om->create('my-source', [])->willReturn($source);

        $conf = new Config('my-import', ['source' => 'my-source']);

        self::assertSame($source, (new SourceFactory($om->reveal()))->create($conf, []));
    }

    public function testCreateWithValidSourceAndArgs() : void
    {
        $om = $this->prophesize(ObjectManagerInterface::class);
        $source = new Iterator(new ArrayIterator([]));
        $om->create('my-source', ['arg' => 'value'])->willReturn($source);

        $conf = new Config('my-import', ['source' => 'my-source']);

        self::assertSame($source, (new SourceFactory($om->reveal()))->create($conf, ['arg' => 'value']));
    }
}
