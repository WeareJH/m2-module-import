<?php

declare(strict_types=1);

namespace Jh\ImportTest\Archiver\Csv\Entity;

use Jh\Import\Archiver\Csv\Entity\Archive;
use Jh\UnitTestHelpers\ObjectHelper;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use PHPUnit\Framework\TestCase;

class ArchiveTest extends TestCase
{
    use ObjectHelper;

    public function testIsFileAvailableReturnsTrueIfNotDeletedOrArchived(): void
    {
        $resource = $this->prophesize(AbstractDb::class);
        $resource->getIdFieldName()->willReturn('id');

        /** @var Archive $archive */
        $archive = $this->getObject(Archive::class, ['resource' => $resource->reveal()]);
        $archive->setData('deleted', false);
        $archive->setData('archived', false);

        self::assertTrue($archive->isFileAvailable());
    }

    public function testIsFileAvailableReturnsFalseIfFileIsArchived(): void
    {
        $resource = $this->prophesize(AbstractDb::class);
        $resource->getIdFieldName()->willReturn('id');

        /** @var Archive $archive */
        $archive = $this->getObject(Archive::class, ['resource' => $resource->reveal()]);
        $archive->setData('deleted', false);
        $archive->setData('archived', true);

        self::assertFalse($archive->isFileAvailable());
    }

    public function testIsFileAvailableReturnsFalseIfFileIsDeleted(): void
    {
        $resource = $this->prophesize(AbstractDb::class);
        $resource->getIdFieldName()->willReturn('id');

        /** @var Archive $archive */
        $archive = $this->getObject(Archive::class, ['resource' => $resource->reveal()]);
        $archive->setData('deleted', false);
        $archive->setData('archived', true);

        self::assertFalse($archive->isFileAvailable());
    }

    public function testIsFileAvailableReturnsFalseIfFileIsDeletedAndArchived(): void
    {
        $resource = $this->prophesize(AbstractDb::class);
        $resource->getIdFieldName()->willReturn('id');

        /** @var Archive $archive */
        $archive = $this->getObject(Archive::class, ['resource' => $resource->reveal()]);
        $archive->setData('deleted', true);
        $archive->setData('archived', true);

        self::assertFalse($archive->isFileAvailable());
    }
}
