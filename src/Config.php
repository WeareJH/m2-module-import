<?php

namespace Jh\Import;

use Magento\Framework\Exception\InvalidArgumentException;

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

    public function getDataRequestFactory(): string
    {
        return $this->getRequired('data_request_factory');
    }

    public function getDataRequestPageSize(): int
    {
        return $this->getRequired('data_request_page_size');
    }

    public function getDataRequestPagingDecorator(): string
    {
        return $this->getRequired('data_request_paging_decorator');
    }

    public function getDataRequestFilterDecorator(): ?string
    {
        return $this->get('data_request_filter_decorator');
    }

    public function getCountRequestFactory(): string
    {
        return $this->getRequired('count_request_factory');
    }

    public function getDataResponseHandler(): string
    {
        return $this->getRequired('data_response_handler');
    }

    public function getCountResponseHandler(): string
    {
        return $this->getRequired('count_response_handler');
    }

    /**
     * @param string $key
     * @return string|null
     */
    public function get(string $key): ?string
    {
        return $this->config[$key] ?? null;
    }

    public function getRequired(string $key): string
    {
        if (empty($this->config[$key])) {
            throw new InvalidArgumentException(__('Required config argument %1 missing', $key));
        }

        return $this->config[$key];
    }

    public function all(): array
    {
        return $this->config;
    }
}
