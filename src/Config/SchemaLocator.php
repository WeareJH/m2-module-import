<?php

namespace Jh\Import\Config;

use Magento\Framework\Config\SchemaLocatorInterface;
use Magento\Framework\Module\Dir;

/**
 * @author Aydin Hassan <aydin@wearejh.com>
 */
class SchemaLocator implements SchemaLocatorInterface
{
    /**
     * @var \Magento\Framework\Config\Dom\UrnResolver
     */
    private $urnResolver;

    /**
     * SchemaLocator constructor.
     * @param \Magento\Framework\Config\Dom\UrnResolver $urnResolver
     */
    public function __construct(\Magento\Framework\Config\Dom\UrnResolver $urnResolver)
    {
        $this->urnResolver = $urnResolver;
    }

    /**
     * {@inheritdoc}
     */
    public function getSchema()
    {
        return $this->urnResolver->getRealPath('urn:magento:module:Jh_Import:etc/imports.xsd');
    }

    /**
     * {@inheritdoc}
     */
    public function getPerFileSchema()
    {
        return $this->urnResolver->getRealPath('urn:magento:module:Jh_Import:etc/imports.xsd');
    }
}
