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
                $item[$this->getData('name')] = format_bytes($item[$this->getData('name')]);
            }

            return $item;
        }, $dataSource['data']['items']);

        return $dataSource;
    }
}
