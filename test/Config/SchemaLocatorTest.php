<?php

namespace Jh\ImportTest\Config;

use Jh\Import\Config\SchemaLocator;
use Magento\Framework\Config\Dom\UrnResolver;
use PHPUnit\Framework\TestCase;

/**
 * @author Aydin Hassan <aydin@wearejh.com>
 */
class SchemaLocatorTest extends TestCase
{
    public function testSchemaLocator(): void
    {
        $urnResolver = new UrnResolver();
        $locator = new SchemaLocator($urnResolver);

        self::assertEquals(
            $urnResolver->getRealPath('urn:magento:module:Jh_Import:etc/imports.xsd'),
            $locator->getPerFileSchema()
        );

        self::assertEquals(
            $urnResolver->getRealPath('urn:magento:module:Jh_Import:etc/imports.xsd'),
            $locator->getSchema()
        );
    }
}
