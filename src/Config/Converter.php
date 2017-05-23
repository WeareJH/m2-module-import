<?php

namespace Jh\Import\Config;

use Illuminate\Support\Collection;
use Magento\Framework\Config\ConverterInterface;

/**
 * @author Aydin Hassan <aydin@wearejh.com>
 */
class Converter implements ConverterInterface
{
    /**
     * @var array
     */
    private static $importTypesWithRequiredFields = [
        'files' => [
            'source',
            'incoming_directory',
            'match_files',
            'specification',
            'writer',
            'id_field'
        ]
    ];

    public function convert($source)
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
                        return $this->getOptions($import, $requiredFields)
                            ->put('type', $importType);
                    });
            });

        return $names->combine($importData)->toArray();
    }

    private function getOptions(\DOMElement $import, array $requiredFields) : Collection
    {
        $options = collect($requiredFields)
            ->map(function ($requiredField) use ($import) {
                /** @var \DOMNodeList $elements */
                $elements = $import->getElementsByTagName($requiredField);
                return $elements->item(0)->nodeValue;
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
