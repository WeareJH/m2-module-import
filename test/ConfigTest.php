<?php

namespace Jh\ImportTest;

use Jh\Import\Config;
use PHPUnit\Framework\TestCase;

/**
 * @author Aydin Hassan <aydin@wearejh.com>
 */
class ConfigTest extends TestCase
{
    public function testGetters()
    {
        $config = new Config('my-import', [
            'type'          => 'files',
            'source'        => 'MySource',
            'specification' => 'MySpecification',
            'writer'        => 'MyWriter',
            'id_field'      => 'sku',
            'arbitrary_1'   => 'some-value',
            'arbitrary_2'   => 'some-value',
            'indexers'      => ['My\Indexer', 'My\OtherIndexer']
        ]);


        self::assertEquals('files', $config->getType());
        self::assertEquals('my-import', $config->getImportName());
        self::assertEquals('MySource', $config->getSourceService());
        self::assertEquals('MyWriter', $config->getWriterService());
        self::assertEquals('some-value', $config->get('arbitrary_1'));
        self::assertEquals('some-value', $config->get('arbitrary_2'));
        self::assertEquals(['My\Indexer', 'My\OtherIndexer'], $config->get('indexers'));
    }

    public function testReportHandlers()
    {
        $config = new Config('my-import', [
            'report_handlers' => ['My\Handler', 'My\OtherHandler']
        ]);

        self::assertEquals(['My\Handler', 'My\OtherHandler'], $config->getReportHandlers());
    }

    public function testReportHandlersDefault()
    {
        $config = new Config('my-import', []);

        self::assertEquals([], $config->getReportHandlers());
    }

    public function testWithNoCron()
    {
        $config = new Config('my-import', []);

        self::assertFalse($config->hasCron());
    }

    public function testWithCron()
    {
        $config = new Config('my-import', ['cron' => 'my-cron-job']);

        self::assertTrue($config->hasCron());
        self::assertEquals('my-cron-job', $config->getCron());
    }

    public function testAllReturnsAllConfig()
    {
        $config = new Config('my-import', [
            'source'        => 'MySource',
            'specification' => 'MySpecification',
            'writer'        => 'MyWriter',
            'id_field'      => 'sku',
            'arbitrary_1'   => 'some-value',
            'arbitrary_2'   => 'some-value',
            'indexers'      => ['My\Indexer', 'My\OtherIndexer']
        ]);

        self::assertSame(
            [
             'source'        => 'MySource',
             'specification' => 'MySpecification',
             'writer'        => 'MyWriter',
             'id_field'      => 'sku',
             'arbitrary_1'   => 'some-value',
             'arbitrary_2'   => 'some-value',
             'indexers'      => ['My\Indexer', 'My\OtherIndexer']
            ],
            $config->all()
        );
    }
}
