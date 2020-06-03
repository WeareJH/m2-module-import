<?php

namespace Jh\ImportTest\Import;

use Jh\Import\Import\Importer;
use Jh\Import\Import\ImporterFactory;
use Jh\Import\Progress\CliProgress;
use Jh\Import\Progress\NullProgress;
use Jh\Import\Source\Source;
use Jh\Import\Specification\ImportSpecification;
use Jh\Import\Writer\Writer;
use Jh\UnitTestHelpers\ObjectHelper;
use Magento\Framework\App\State;
use phpmock\MockBuilder;
use PHPUnit\Framework\TestCase;

/**
 * @author Aydin Hassan <aydin@wearejh.com>
 */
class ImporterFactoryTest extends TestCase
{
    use ObjectHelper;

    /**
     * @runInSeparateProcess
     */
    public function testImporterIsCreatedWithoutProgressWhenNotInDevModeOrNoTty()
    {
        $builder = new MockBuilder();
        $builder->setNamespace('Jh\Import\Import')
            ->setName('posix_isatty')
            ->setFunction(
                function () {
                    return false;
                }
            );

        $mock = $builder->build();
        $mock->enable();

        $appState = $this->prophesize(State::class);
        $appState->getMode()->willReturn(State::MODE_PRODUCTION);

        $importerFactory = $this->getObject(ImporterFactory::class, [
            'appState' => $appState->reveal()
        ]);

        $source          = $this->prophesize(Source::class);
        $importSpec      = $this->prophesize(ImportSpecification::class);
        $writer          = $this->prophesize(Writer::class);

        $importer = $importerFactory->create($source->reveal(), $importSpec->reveal(), $writer->reveal());

        self::assertInstanceOf(Importer::class, $importer);
        self::assertInstanceOf(NullProgress::class, $importer->getProgress());

        $mock->disable();
    }

    public function testImporterCreatedWithCliProgressInDevMode()
    {
        $appState = $this->prophesize(State::class);
        $appState->getMode()->willReturn(State::MODE_DEVELOPER);

        $importerFactory = $this->getObject(ImporterFactory::class, [
            'appState' => $appState->reveal()
        ]);

        $source     = $this->prophesize(Source::class);
        $importSpec = $this->prophesize(ImportSpecification::class);
        $writer     = $this->prophesize(Writer::class);

        $importer = $importerFactory->create($source->reveal(), $importSpec->reveal(), $writer->reveal());

        self::assertInstanceOf(Importer::class, $importer);
        self::assertInstanceOf(CliProgress::class, $importer->getProgress());
    }

    /**
     * @runInSeparateProcess
     */
    public function testImporterCreatedWithCliProgressWhenTty()
    {
        $builder = new MockBuilder();
        $builder->setNamespace('Jh\Import\Import')
            ->setName('posix_isatty')
            ->setFunction(
                function () {
                    return true;
                }
            );

        $mock = $builder->build();
        $mock->enable();

        $appState = $this->prophesize(State::class);
        $appState->getMode()->willReturn(State::MODE_PRODUCTION);

        $importerFactory = $this->getObject(ImporterFactory::class, [
            'appState' => $appState->reveal()
        ]);

        $source     = $this->prophesize(Source::class);
        $importSpec = $this->prophesize(ImportSpecification::class);
        $writer     = $this->prophesize(Writer::class);

        $importer = $importerFactory->create($source->reveal(), $importSpec->reveal(), $writer->reveal());

        self::assertInstanceOf(Importer::class, $importer);
        self::assertInstanceOf(CliProgress::class, $importer->getProgress());

        $mock->disable();
    }
}
