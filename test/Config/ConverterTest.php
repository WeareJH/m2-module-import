<?php

namespace Jh\ImportTest\Config;

use Jh\Import\Config\Converter;
use PHPUnit\Framework\TestCase;

/**
 * @author Aydin Hassan <aydin@wearejh.com>
 */
class ConverterTest extends TestCase
{
    public function testConvert()
    {
        $xml = <<<'END'
<?xml version="1.0"?>
<config xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="imports.xsd">
    <files name="product">
        <source>Jh\Import\Source\Csv</source>
        <incoming_directory>jh_import/incoming</incoming_directory>
        <match_files>rdrive.csv</match_files>
        <specification>Jh\Import\Specification\Product</specification>
        <writer>Jh\Import\Writer\Product</writer>
        <id_field>sku</id_field>
    </files>
</config>
END;

        $domDocument = new \DOMDocument();
        $domDocument->loadXML($xml);

        self::assertEquals(
            [
                'product' => [
                    'source' => 'Jh\Import\Source\Csv',
                    'incoming_directory' => 'jh_import/incoming',
                    'match_files' => 'rdrive.csv',
                    'specification' => 'Jh\Import\Specification\Product',
                    'writer' => 'Jh\Import\Writer\Product',
                    'type' => 'files',
                    'id_field' => 'sku',
                    'indexers' => [],
                    'report_handlers' => []
                ]
            ],
            (new Converter)->convert($domDocument)
        );
    }

    public function testConvertWithOptionalIndexers()
    {
        $xml = <<<'END'
<?xml version="1.0"?>
<config xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="imports.xsd">
    <files name="product">
        <source>Jh\Import\Source\Csv</source>
        <incoming_directory>jh_import/incoming</incoming_directory>
        <match_files>rdrive.csv</match_files>
        <specification>Jh\Import\Specification\Product</specification>
        <writer>Jh\Import\Writer\Product</writer>
        <id_field>sku</id_field>
        <indexers>
            <indexer>My\Indexer</indexer>
            <indexer>My\OtherIndexer</indexer>
        </indexers>
    </files>
</config>
END;

        $domDocument = new \DOMDocument();
        $domDocument->loadXML($xml);

        self::assertEquals(
            [
                'product' => [
                    'source' => 'Jh\Import\Source\Csv',
                    'incoming_directory' => 'jh_import/incoming',
                    'match_files' => 'rdrive.csv',
                    'specification' => 'Jh\Import\Specification\Product',
                    'writer' => 'Jh\Import\Writer\Product',
                    'type' => 'files',
                    'id_field' => 'sku',
                    'indexers' => [
                        'My\Indexer',
                        'My\OtherIndexer'
                    ],
                    'report_handlers' => []
                ]
            ],
            (new Converter)->convert($domDocument)
        );
    }

    public function testConvertWithNoAdditionalReportHandlers()
    {
        $xml = <<<'END'
<?xml version="1.0"?>
<config xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="imports.xsd">
    <files name="product">
        <source>Jh\Import\Source\Csv</source>
        <incoming_directory>jh_import/incoming</incoming_directory>
        <match_files>rdrive.csv</match_files>
        <specification>Jh\Import\Specification\Product</specification>
        <writer>Jh\Import\Writer\Product</writer>
        <id_field>sku</id_field>
    </files>
</config>
END;

        $domDocument = new \DOMDocument();
        $domDocument->loadXML($xml);

        self::assertEquals(
            [
                'product' => [
                    'source' => 'Jh\Import\Source\Csv',
                    'incoming_directory' => 'jh_import/incoming',
                    'match_files' => 'rdrive.csv',
                    'specification' => 'Jh\Import\Specification\Product',
                    'writer' => 'Jh\Import\Writer\Product',
                    'type' => 'files',
                    'id_field' => 'sku',
                    'indexers' => [],
                    'report_handlers' => []
                ]
            ],
            (new Converter)->convert($domDocument)
        );
    }

    public function testConvertWithOptionalReportHandlers()
    {
        $xml = <<<'END'
<?xml version="1.0"?>
<config xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="imports.xsd">
    <files name="product">
        <source>Jh\Import\Source\Csv</source>
        <incoming_directory>jh_import/incoming</incoming_directory>
        <match_files>rdrive.csv</match_files>
        <specification>Jh\Import\Specification\Product</specification>
        <writer>Jh\Import\Writer\Product</writer>
        <id_field>sku</id_field>
        <report_handlers>
            <report_handler>My\ReportHandler</report_handler>
            <report_handler>My\OtherReportHandler</report_handler>
        </report_handlers>
    </files>
</config>
END;

        $domDocument = new \DOMDocument();
        $domDocument->loadXML($xml);

        self::assertEquals(
            [
                'product' => [
                    'source' => 'Jh\Import\Source\Csv',
                    'incoming_directory' => 'jh_import/incoming',
                    'match_files' => 'rdrive.csv',
                    'specification' => 'Jh\Import\Specification\Product',
                    'writer' => 'Jh\Import\Writer\Product',
                    'type' => 'files',
                    'id_field' => 'sku',
                    'indexers' => [],
                    'report_handlers' => [
                        'My\ReportHandler',
                        'My\OtherReportHandler'
                    ],
                ]
            ],
            (new Converter)->convert($domDocument)
        );
    }
}
