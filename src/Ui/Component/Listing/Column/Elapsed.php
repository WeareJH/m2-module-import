<?php

namespace Jh\Import\Ui\Component\Listing\Column;

use Magento\Ui\Component\Listing\Columns\Column;

/**
 * @author Aydin Hassan <aydin@wearejh.com>
 */
class Elapsed extends Column
{
    private static $parts = [
        'd' => 'Day',
        'h' => 'Hour',
        'i' => 'Minute',
    ];

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

            if (!isset($item['started']) || !isset($item['finished'])) {
                return $item;
            }

            $started  = new \DateTime($item['started']);
            $finished = new \DateTime($item['finished']);

            $diff = $started->diff($finished);
            $elapsedParts = array_filter(
                array_map(
                    function ($part, $label) use ($diff) {
                        if ($diff->{$part} > 0) {
                            return sprintf('%d %s%s', $diff->{$part}, $label, $diff->{$part} > 1 ? 's' : '');
                        }
                        return null;
                    },
                    array_keys(static::$parts),
                    static::$parts
                )
            );

            $item[$this->getData('name')] = implode(', ', $elapsedParts);
            return $item;
        }, $dataSource['data']['items']);

        return $dataSource;
    }
}
