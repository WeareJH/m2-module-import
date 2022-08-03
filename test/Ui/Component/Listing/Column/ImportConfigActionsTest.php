<?php

namespace Jh\ImportTest\Ui\Component\Listing\Column;

use Jh\Import\Ui\Component\Listing\Column\ImportConfigActions;
use Jh\UnitTestHelpers\ObjectHelper;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponent\Processor;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;

/**
 * @author Aydin Hassan <aydin@wearejh.com>
 */
class ImportConfigActionsTest extends TestCase
{
    use ObjectHelper;
    use ProphecyTrait;

    public function testInfoLinksAreAdded(): void
    {
        $urlBuilder = $this->prophesize(UrlInterface::class);
        $context = $this->prophesize(ContextInterface::class);
        $context->getProcessor()->willReturn($this->prophesize(Processor::class)->reveal());

        $actions = $this->getObject(
            ImportConfigActions::class,
            [
                'urlBuilder' => $urlBuilder->reveal(),
                'context' => $context->reveal(),
                'data' => ['name' => 'actions']
            ]
        );

        $urlBuilder
            ->getUrl('jh_import/config/info', ['name' => 'product'])
            ->willReturn('jh_import/config/info/name/product');

        $urlBuilder
            ->getUrl('jh_import/config/info', ['name' => 'stock'])
            ->willReturn('jh_import/config/info/name/stock');

        $data = [
            'data' => [
                'items' => [
                    ['name' => 'product'],
                    ['name' => 'stock'],
                ]
            ]
        ];

        $expected = [
            'data' => [
                'items' => [
                    [
                        'name' => 'product',
                        'actions' => [
                            'info' => [
                                'href' => 'jh_import/config/info/name/product',
                                'label' => 'Info'
                            ]
                        ]
                    ],
                    [
                        'name' => 'stock',
                        'actions' => [
                            'info' => [
                                'href' => 'jh_import/config/info/name/stock',
                                'label' => 'Info'
                            ]
                        ]
                    ],
                ]
            ]
        ];

        self::assertEquals($expected, $actions->prepareDataSource($data));
    }
}
