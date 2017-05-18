<?php

namespace Jh\ImportTest\Archiver;

use Jh\Import\Archiver\Archiver;
use Jh\Import\Archiver\CsvArchiver;
use Jh\Import\Archiver\Factory;
use Jh\Import\Archiver\NullArchiver;
use Jh\Import\Source\Csv;
use Jh\Import\Source\Source;
use Magento\Framework\ObjectManagerInterface;
use PHPUnit\Framework\TestCase;

/**
 * @author Aydin Hassan <aydin@wearejh.com>
 */
class FactoryTest extends TestCase
{
    public function testCsvSourceProducesCsvArchiver()
    {
        $archiver = $this->prophesize(Archiver::class)->reveal();
        $source = (new \ReflectionClass(Csv::class))->newInstanceWithoutConstructor();

        $om = $this->prophesize(ObjectManagerInterface::class);
        $om->create(CsvArchiver::class, ['source' => $source])->willReturn($archiver)->shouldBeCalled();

        self::assertSame(
            (new Factory($om->reveal()))->getArchiverForSource($source),
            $archiver
        );
    }

    public function testUnknowSourceProducesNullArchiver()
    {
        $source = $this->prophesize(Source::class)->reveal();
        $om = $this->prophesize(ObjectManagerInterface::class)->reveal();

        self::assertInstanceOf(
            NullArchiver::class,
            (new Factory($om))->getArchiverForSource($source)
        );
    }
}
