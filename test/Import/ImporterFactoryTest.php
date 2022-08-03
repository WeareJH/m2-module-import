<?php

namespace Jh\ImportTest\Import;

use Jh\Import\Import\Importer;
use Jh\Import\Import\ImporterFactory;
use Jh\Import\Output\Factory as OutputFactory;
use Jh\Import\Progress\Factory as ProgressFactory;
use Jh\Import\Progress\Progress;
use Jh\Import\Source\Source;
use Jh\Import\Specification\ImportSpecification;
use Jh\Import\Writer\Writer;
use Jh\UnitTestHelpers\ObjectHelper;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\Console\Output\OutputInterface;

class ImporterFactoryTest extends TestCase
{
    use ObjectHelper;
    use ProphecyTrait;

    public function testFactory(): void
    {
        $source = $this->prophesize(Source::class);
        $importSpec = $this->prophesize(ImportSpecification::class);
        $writer = $this->prophesize(Writer::class);

        $outputFactory = $this->prophesize(OutputFactory::class);
        $progressFactory = $this->prophesize(ProgressFactory::class);

        $output = $this->prophesize(OutputInterface::class);
        $progress = $this->prophesize(Progress::class);

        $outputFactory->get()->willReturn($output);
        $progressFactory->get()->willReturn($progress);

        $importerFactory = $this->getObject(ImporterFactory::class, [
            'progressFactory' => $progressFactory->reveal(),
            'outputFactory' => $outputFactory->reveal(),
        ]);

        $importer = $importerFactory->create($source->reveal(), $importSpec->reveal(), $writer->reveal());

        self::assertInstanceOf(Importer::class, $importer);
    }
}
