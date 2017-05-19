<?php

namespace Jh\ImportTest\Ui\Component\Listing\Column;

use Jh\Import\Ui\Component\Listing\Column\ImportHistoryActions;
use Jh\UnitTestHelpers\ObjectHelper;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponent\Processor;
use PHPUnit\Framework\TestCase;

/**
 * @author Aydin Hassan <aydin@wearejh.com>
 */
class ImportHistoryActionsTest extends TestCase
{
    use ObjectHelper;

    public function testEditLinksAreAdded()
    {
        $urlBuilder = $this->prophesize(UrlInterface::class);
        $context    = $this->prophesize(ContextInterface::class);
        $context->getProcessor()->willReturn($this->prophesize(Processor::class)->reveal());

        $actions = $this->getObject(
            ImportHistoryActions::class,
            [
                'urlBuilder' => $urlBuilder->reveal(),
                'context' => $context->reveal(),
                'data' => ['name' => 'actions']
            ]
        );

        $urlBuilder
            ->getUrl('jh_import/log/import', ['history_id' => 10])
            ->willReturn('jh_import/log/import/history_id/10');

        $urlBuilder
            ->getUrl('jh_import/log/import', ['history_id' => 11])
            ->willReturn('jh_import/log/import/history_id/11');

        $data = [
            'data' => [
                'items' => [
                    ['id' => 10],
                    ['id' => 11],
                ]
            ]
        ];

        $expected = [
            'data' => [
                'items' => [
                    [
                        'id' => 10,
                        'actions' => [
                            'view' => [
                                'href' => 'jh_import/log/import/history_id/10',
                                'label' => 'View Log'
                            ]
                        ]
                    ],
                    [
                        'id' => 11,
                        'actions' => [
                            'view' => [
                                'href' => 'jh_import/log/import/history_id/11',
                                'label' => 'View Log'
                            ]
                        ]
                    ],
                ]
            ]
        ];

        self::assertEquals($expected, $actions->prepareDataSource($data));
    }
}
