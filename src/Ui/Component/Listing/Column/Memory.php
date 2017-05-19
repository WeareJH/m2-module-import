<?php

namespace Jh\Import\Ui\Component\Listing\Column;

use Magento\Ui\Component\Listing\Columns\Column;

/**
 * @author Aydin Hassan <aydin@wearejh.com>
 */
class Memory extends Column
{
    /**
     * Prepare Data Source
     *
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        if (!isset($dataSource['data']['items'])) {
            return $dataSource;
        }

        $dataSource['data']['items'] = array_map(function (array $item) {

            if (isset($item[$this->getData('name')])) {
                $item[$this->getData('name')] = $this->formatBytes($item[$this->getData('name')]);
            }

            return $item;
        }, $dataSource['data']['items']);

        return $dataSource;
    }

    private function formatBytes(string $bytes) : string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        $bytes = max($bytes, 0);
        $pow   = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow   = min($pow, count($units) - 1);

        $bytes /= (1 << (10 * $pow));

        return round($bytes, 2) . ' ' . $units[$pow];
    }
}
