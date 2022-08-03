<?php

namespace Jh\ImportTest\Entity;

use Jh\Import\Entity\ImportHistory;
use Jh\UnitTestHelpers\ObjectHelper;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;

/**
 * @author Aydin Hassan <aydin@wearejh.com>
 */
class ImportHistoryTest extends TestCase
{
    use ObjectHelper;
    use ProphecyTrait;

    public function testGetStartedAt(): void
    {
        $resource = $this->prophesize(AbstractDb::class);
        $resource->getIdFieldName()->willReturn('id');

        $importHistory = $this->getObject(ImportHistory::class, ['resource' => $resource->reveal()]);
        $importHistory->setData('started', '2017-03-10 14:53:12');

        $date = $importHistory->getStartedAt();
        self::assertInstanceOf(\DateTime::class, $date);
        self::assertEquals('2017-03-10 14:53:12', $date->format('Y-m-d H:i:s'));
    }
}
