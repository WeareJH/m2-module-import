<?php

namespace Jh\Import;

/**
 * @author Aydin Hassan <aydin@wearejh.com>
 */
class Config
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var array
     */
    private $config;

    public function __construct(string $name, array $config)
    {
        $this->name = $name;
        $this->config = $config;
    }

    public function getImportName() : string
    {
        return $this->name;
    }

    public function getSourceService() : string
    {
        return $this->config['source'];
    }

    public function getSpecificationService() : string
    {
        return $this->config['specification'];
    }

    public function getWriterService() : string
    {
        return $this->config['writer'];
    }

    public function getIdField() : string
    {
        return $this->config['id_field'];
    }

    public function getIndexers() : array
    {
        return $this->config['indexers'] ?? [];
    }

    /**
     * @param string $key
     * @return string|null
     */
    public function get(string $key)
    {
        return $this->config[$key] ?? null;
    }
}
