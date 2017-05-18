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
        <source>Fps\Import\Source\Csv</source>
        <incoming_directory>fps_import/incoming</incoming_directory>
        <match_files>rdrive.csv</match_files>
        <specification>Fps\Import\Specification\Product</specification>
        <writer>Fps\Import\Model\Product\ProductWriter</writer>
        <id_field>sku</id_field>
    </files>
</config>
END;

        $domDocument = new \DOMDocument();
        $domDocument->loadXML($xml);

        self::assertEquals(
            [
                'product' => [
                    'source' => 'Fps\Import\Source\Csv',
                    'incoming_directory' => 'fps_import/incoming',
                    'match_files' => 'rdrive.csv',
                    'specification' => 'Fps\Import\Specification\Product',
                    'writer' => 'Fps\Import\Model\Product\ProductWriter',
                    'type' => 'files',
                    'id_field' => 'sku',
                    'indexers' => []
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
        <source>Fps\Import\Source\Csv</source>
        <incoming_directory>fps_import/incoming</incoming_directory>
        <match_files>rdrive.csv</match_files>
        <specification>Fps\Import\Specification\Product</specification>
        <writer>Fps\Import\Model\Product\ProductWriter</writer>
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
                    'source' => 'Fps\Import\Source\Csv',
                    'incoming_directory' => 'fps_import/incoming',
                    'match_files' => 'rdrive.csv',
                    'specification' => 'Fps\Import\Specification\Product',
                    'writer' => 'Fps\Import\Model\Product\ProductWriter',
                    'type' => 'files',
                    'id_field' => 'sku',
                    'indexers' => [
                        'My\Indexer',
                        'My\OtherIndexer'
                    ]
                ]
            ],
            (new Converter)->convert($domDocument)
        );
    }
}
