<?php

namespace Jh\Import\Config;

use Illuminate\Support\Collection;
use Magento\Framework\Config\ConverterInterface;

class Converter implements ConverterInterface
{
    /**
     * @var array
     */
    private static $importTypesWithRequiredFields = [
        'files' => [
            'source' => ['type' => 'string', 'default' => \Jh\Import\Source\Csv::class],
            'incoming_directory' => ['type' => 'string', 'default' => 'jh_import/incoming'],
            'archived_directory' => ['type' => 'string', 'default' => 'jh_import/archived'],
            'failed_directory' => ['type' => 'string', 'default' => 'jh_import/failed'],
            'match_files' => ['type' => 'string'],
            'specification' => ['type' => 'string'],
            'writer' => ['type' => 'string'],
            'id_field' => ['type' => 'string'],
            'cron' => ['type' => 'string'],
            'cron_group' => ['type' => 'string', 'default' => 'default'],
            'archive_old_files' => ['type' => 'bool', 'default' => false],
            'delete_old_files' => ['type' => 'bool', 'default' => false],
            'archive_date_format' => ['type' => 'string', 'default' => 'dmYhis'],
            'directory_permissions' => ['type' => 'int', 'default' => 0755],
        ],
        'db' => [
            'connection_name' => ['type' => 'string'],
            'source' => ['type' => 'string', 'default' => \Jh\Import\Source\Db::class],
            'specification' => ['type' => 'string'],
            'writer' => ['type' => 'string'],
            'id_field' => ['type' => 'string'],
            'source_id' => ['type' => 'string'],
            'select_sql' => ['type' => 'string'],
            'count_sql' => ['type' => 'string'],
            'cron' => ['type' => 'string'],
            'cron_group' => ['type' => 'string', 'default' => 'default']
        ],
        'webapi' => [
            'source' => ['type' => 'string', 'default' => \Jh\Import\Source\Webapi::class],
            'source_id' => ['type' => 'string'],
            'specification' => ['type' => 'string'],
            'writer' => ['type' => 'string'],
            'id_field' => ['type' => 'string'],
            'count_request_factory' => ['type' => 'string'],
            'count_response_handler' => ['type' => 'string'],
            'data_request_factory' => ['type' => 'string'],
            'data_request_page_size' => ['type' => 'int'],
            'data_request_paging_decorator' => ['type' => 'string'],
            'data_request_filter_decorator' => ['type' => 'string'],
            'data_response_handler' => ['type' => 'string'],
            'cron' => ['type' => 'string'],
            'cron_group' => ['type' => 'string', 'default' => 'default']
        ]
    ];

    private AppConfigProvider $appConfigProvider;

    public function __construct(AppConfigProvider $appConfigProvider)
    {
        $this->appConfigProvider = $appConfigProvider;
    }

    public function convert($source): array
    {
        $names = collect(static::$importTypesWithRequiredFields)
            ->keys()
            ->flatMap(function ($importType) use ($source) {
                /** @var \DOMNodeList $imports */
                $imports = $source->getElementsByTagName($importType);

                return collect($imports)
                    ->map(function (\DOMElement $import) {
                        return $import->getAttribute('name');
                    });
            });

        $importData = collect(static::$importTypesWithRequiredFields)
            ->flatMap(function (array $requiredFields, $importType) use ($source) {
                /** @var \DOMNodeList $imports */
                $imports = $source->getElementsByTagName($importType);

                return collect($imports)
                    ->map(function (\DOMElement $import) use ($importType, $requiredFields) {
                        return $this->getOptions($import, $requiredFields, $importType)
                            ->put('type', $importType);
                    });
            });

        return $names->combine($importData)->toArray();
    }

    private function getOptions(\DOMElement $import, array $requiredFields, string $importType): Collection
    {
        $options = collect($requiredFields)
            ->map(function (array $spec, string $requiredField) use ($import, $importType) {
                /** @var \DOMNodeList $elements */
                $elements = $import->getElementsByTagName($requiredField);

                // load default value from app config
                $value = $this->appConfigProvider->getImportTypeOptionDefaultValue($importType, $requiredField);

                // override by import config
                if ($elements->length > 0) {
                    $value = $elements->item(0)->nodeValue;
                }

                if ($value !== null) {
                    switch ($spec['type']) {
                        case 'bool':
                            return $this->castBool($value);
                        case 'int':
                            return $this->castInt($value);
                        case 'string':
                            return $this->castString($value);
                    }
                }

                return $value ?? $spec['default'] ?? null;
            });

        //parse required indexers
        $indexersElement = $import->getElementsByTagName('indexers');
        $indexers = [];
        if ($indexersElement->length > 0) {
            $indexers = collect($indexersElement->item(0)->getElementsByTagName('indexer'))
                ->map(function (\DOMElement $indexer) {
                    return $indexer->nodeValue;
                })
                ->all();
        }

        //parse report handlers
        $reportHandlersElement = $import->getElementsByTagName('report_handlers');
        $reportHandlers = [];
        if ($reportHandlersElement->length > 0) {
            $reportHandlers = collect($reportHandlersElement->item(0)->getElementsByTagName('report_handler'))
                ->map(function (\DOMElement $reportHandler) {
                    return $reportHandler->nodeValue;
                })
                ->all();
        }

        return collect($requiredFields)
            ->keys()
            ->combine($options)
            ->put('indexers', $indexers)
            ->put('report_handlers', $reportHandlers);
    }

    private function castString($value): string
    {
        return (string) $value;
    }

    private function castInt($value): int
    {
        return (int) $value;
    }

    private function castBool($value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        return $value === 'true' || $value === '1';
    }
}
