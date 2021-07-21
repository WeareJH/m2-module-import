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

    public function getImportName(): string
    {
        return $this->name;
    }

    public function getType(): string
    {
        return $this->config['type'];
    }

    public function getSourceService(): string
    {
        return $this->config['source'];
    }

    public function getSpecificationService(): string
    {
        return $this->config['specification'];
    }

    public function getWriterService(): string
    {
        return $this->config['writer'];
    }

    public function getIdField(): string
    {
        return $this->config['id_field'];
    }

    public function getIndexers(): array
    {
        return $this->config['indexers'] ?? [];
    }

    public function getReportHandlers(): array
    {
        return $this->config['report_handlers'] ?? [];
    }

    public function hasCron(): bool
    {
        return isset($this->config['cron']);
    }

    public function getCron(): ?string
    {
        return $this->config['cron'] ?? null;
    }

    public function getCronGroup(): ?string
    {
        return $this->config['cron_group'] ?? null;
    }

    public function getConnectionName(): ?string
    {
        return $this->config['connection_name'] ?? null;
    }

    public function getSourceId(): ?string
    {
        return $this->config['source_id'] ?? null;
    }

    public function getSelectSql(): ?string
    {
        return $this->config['select_sql'] ?? null;
    }

    public function getCountSql(): ?string
    {
        return $this->config['count_sql'] ?? null;
    }

    /**
     * @param string $key
     * @return string|null
     */
    public function get(string $key)
    {
        return $this->config[$key] ?? null;
    }

    public function all(): array
    {
        return $this->config;
    }
}
