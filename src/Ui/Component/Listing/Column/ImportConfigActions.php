<?php

namespace Jh\Import\Ui\Component\Listing\Column;

use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

/**
 * @author Aydin Hassan <aydin@wearejh.com>
 */
class ImportConfigActions extends Column
{
    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    /**
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param UrlInterface $urlBuilder
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        UrlInterface $urlBuilder,
        array $components = [],
        array $data = []
    ) {
        $this->urlBuilder = $urlBuilder;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

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
            return array_merge(
                $item,
                [
                    $this->getData('name') => [
                        'info' => [
                            'href' => $this->urlBuilder->getUrl(
                                'jh_import/config/info',
                                [
                                    'name' => $item['name']
                                ]
                            ),
                            'label' => __('Info')
                        ]
                    ],
                ]
            );
        }, $dataSource['data']['items']);

        return $dataSource;
    }
}
