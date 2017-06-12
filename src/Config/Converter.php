<?php

namespace Jh\Import\Config;

use Illuminate\Support\Collection;
use Magento\Framework\Config\ConverterInterface;

/**
 * @author Aydin Hassan <aydin@wearejh.com>
 */
class Converter implements ConverterInterface
{
    private static $defaultValues = [
        'files' => [
            'incoming_directory' => 'jh_import/incoming',
            'archived_directory' => 'jh_import/archived',
            'failed_directory'   => 'jh_import/failed',
        ]
    ];

    /**
     * @var array
     */
    private static $importTypesWithRequiredFields = [
        'files' => [
            'source',
            'incoming_directory',
            'archived_directory',
            'failed_directory',
            'match_files',
            'specification',
            'writer',
            'id_field',
            'cron'
        ]
    ];

    public function convert($source) : array
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

    private function getOptions(\DOMElement $import, array $requiredFields, string $importType) : Collection
    {
        $options = collect($requiredFields)
            ->map(function ($requiredField) use ($import, $importType) {
                /** @var \DOMNodeList $elements */
                $elements = $import->getElementsByTagName($requiredField);

                if ($elements->length > 0) {
                    return $elements->item(0)->nodeValue;
                }

                if (isset(static::$defaultValues[$importType][$requiredField])) {
                    return static::$defaultValues[$importType][$requiredField];
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
            ->combine($options)
            ->put('indexers', $indexers)
            ->put('report_handlers', $reportHandlers);
    }
}
