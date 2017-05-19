<?php

namespace Jh\ImportTest\Ui\Component\Listing\Column;

use Jh\Import\Ui\Component\Listing\Column\Memory;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Framework\View\Element\UiComponent\Processor;
use PHPUnit\Framework\TestCase;

/**
 * @author Aydin Hassan <aydin@wearejh.com>
 */
class MemoryTest extends TestCase
{
    /**
     * @param string $bytes
     * @param string $expected
     * @dataProvider bytesProvider
     */
    public function testMemoryIsFormattedFromBytes(string $bytes, string $expected)
    {
        $context            = $this->prophesize(ContextInterface::class);
        $uiComponentFactory = $this->prophesize(UiComponentFactory::class);

        $context->getProcessor()->willReturn($this->prophesize(Processor::class)->reveal());

        $memory = new Memory($context->reveal(), $uiComponentFactory->reveal(), [], ['name' => 'memory']);


        self::assertSame(
            ['data' => ['items' => [['memory' => $expected]]]],
            $memory->prepareDataSource(['data' => ['items' => [['memory' => $bytes]]]])
        );
    }

    public function bytesProvider() : array
    {
        return [
            ['2147483700', '2 GB'],
            ['2097152', '2 MB'],
            ['1050', '1.03 KB'],
            ['500', '500 B']
        ];
    }
}
