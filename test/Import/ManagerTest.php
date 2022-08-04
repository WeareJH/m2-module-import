<?php

namespace Jh\ImportTest\Import;

use Jh\Import\Config\Data;
use Jh\Import\Config;
use Jh\Import\Import\Manager;
use Jh\Import\Type\Files;
use Jh\Import\Type\Type;
use Magento\Framework\ObjectManagerInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;

/**
 * @author Aydin Hassan <aydin@wearejh.com>
 */
class ManagerTest extends TestCase
{
    use ProphecyTrait;

    public function testExceptionIsThrownIfImportWithNameDoesNotExist(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot find configuration for import with name: "not-valid-import"');


        $config = $this->prophesize(Data::class);
        $om = $this->prophesize(ObjectManagerInterface::class);

        $config->hasImport('not-valid-import')->willReturn(false);

        (new Manager($config->reveal(), $om->reveal()))->executeImportByName('not-valid-import');
    }

    public function testExceptionIsThrownIfInvalidType(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Import configuration specified invalid type: "not-a-valid-type". Valid types are: "files"'
        );

        $config = $this->prophesize(Data::class);
        $om = $this->prophesize(ObjectManagerInterface::class);

        $config->hasImport('some-import')->willReturn(true);
        $config->getImportType('some-import')->willReturn('not-a-valid-type');

        (new Manager($config->reveal(), $om->reveal()))->executeImportByName('some-import');
    }

    public function testExceptionIsThrownIfTypeDoesNotImplementCorrectInterface(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(
            'Import type: "stdClass" does not implement require interface: "Jh\Import\Type\Type"'
        );

        $config = $this->prophesize(Data::class);
        $om = $this->prophesize(ObjectManagerInterface::class);

        $config->hasImport('some-import')->willReturn(true);
        $config->getImportType('some-import')->willReturn('files');
        $om->get(Files::class)->willReturn(new \stdClass());

        (new Manager($config->reveal(), $om->reveal()))->executeImportByName('some-import');
    }

    public function testImportTypeIsInvokedIfConfigCorrect(): void
    {
        $config = $this->prophesize(Data::class);
        $om = $this->prophesize(ObjectManagerInterface::class);
        $type = $this->prophesize(Type::class);

        $importConfig = new Config('some-import', ['option1' => 'value1']);
        $config->hasImport('some-import')->willReturn(true);
        $config->getImportType('some-import')->willReturn('files');
        $config->getImportConfigByName('some-import')->willReturn($importConfig);
        $om->get(Files::class)->willReturn($type->reveal());

        (new Manager($config->reveal(), $om->reveal()))->executeImportByName('some-import');

        $type->run($importConfig)->shouldHaveBeenCalled();
    }
}
