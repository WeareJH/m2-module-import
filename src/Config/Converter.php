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
            'source' => ['type' => 'string'],
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
        ],
        'db' => [
            'connection_name' => ['type' => 'string'],
            'source' => ['type' => 'string'],
            'specification' => ['type' => 'string'],
            'writer' => ['type' => 'string'],
            'id_field' => ['type' => 'string'],
            'source_id' => ['type' => 'string'],
            'select_sql' => ['type' => 'string'],
            'count_sql' => ['type' => 'string'],
            'cron' => ['type' => 'string'],
            'cron_group' => ['type' => 'string', 'default' => 'default']
        ]
    ];

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

                if ($elements->length > 0) {
                    $value = $elements->item(0)->nodeValue;

                    switch ($spec['type']) {
                        case 'bool':
                            return $this->castBool($value);
                        case 'string':
                            return $this->castString($value);
                    }

                    return $value;
                }

                if (isset($spec['default'])) {
                    return $spec['default'];
                }

                return null;
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

    private function castBool($value): bool
    {
        return $value === 'true' || $value === '1';
    }
}
