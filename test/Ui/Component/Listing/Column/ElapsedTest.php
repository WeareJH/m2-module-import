<?php

namespace Jh\ImportTest\Ui\Component\Listing\Column;

use Jh\Import\Ui\Component\Listing\Column\Elapsed;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Framework\View\Element\UiComponent\Processor;
use PHPUnit\Framework\TestCase;

/**
 * @author Aydin Hassan <aydin@wearejh.com>
 */
class ElapsedTest extends TestCase
{
    public function testElapsedIsIgnoredIfBothDatesDoNotExist()
    {
        $context            = $this->prophesize(ContextInterface::class);
        $uiComponentFactory = $this->prophesize(UiComponentFactory::class);

        $context->getProcessor()->willReturn($this->prophesize(Processor::class)->reveal());

        $elapsed = new Elapsed($context->reveal(), $uiComponentFactory->reveal(), [], ['name' => 'elapsed']);

        self::assertArrayNotHasKey(
            'elapsed',
            $elapsed->prepareDataSource(['data' => ['items' => [[]]]])
        );
        self::assertArrayNotHasKey(
            'elapsed',
            $elapsed->prepareDataSource(['data' => ['items' => [['started' => '28-03-2017']]]])
        );
        self::assertArrayNotHasKey(
            'elapsed',
            $elapsed->prepareDataSource(['data' => ['items' => [['finished' => '28-03-2017']]]])
        );
    }

    /**
     * @param string $started
     * @param string $finished
     * @param string $expected
     * @dataProvider dateProvider
     */
    public function testElapsedIsAddedAndCorrectWhenBothDatesExist(string $started, string $finished, string $expected)
    {
        $context            = $this->prophesize(ContextInterface::class);
        $uiComponentFactory = $this->prophesize(UiComponentFactory::class);

        $context->getProcessor()->willReturn($this->prophesize(Processor::class)->reveal());

        $elapsed = new Elapsed($context->reveal(), $uiComponentFactory->reveal(), [], ['name' => 'elapsed']);

        self::assertSame(
            ['data' => ['items' => [['started' => $started, 'finished' => $finished, 'elapsed' => $expected]]]],
            $elapsed->prepareDataSource(['data' => ['items' => [['started' => $started, 'finished' => $finished]]]])
        );
    }

    public function dateProvider() : array
    {
        return [
            ['2017-03-27 15:07:02', '2017-03-27 17:17:02', '2 Hours, 10 Minutes'],
            ['2017-03-25 15:07:02', '2017-03-27 17:17:02', '2 Days, 2 Hours, 10 Minutes'],
            ['2017-03-27 17:07:02', '2017-03-27 17:17:02', '10 Minutes'],
            ['2017-03-27 17:16:02', '2017-03-27 17:17:02', '1 Minute'],
            ['2017-03-26 16:16:02', '2017-03-27 17:17:02', '1 Day, 1 Hour, 1 Minute'],
        ];
    }
}
