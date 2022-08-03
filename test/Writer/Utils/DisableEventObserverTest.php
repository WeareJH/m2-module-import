<?php

declare(strict_types=1);

namespace Jh\ImportTest\Writer\Util;

use Jh\Import\Writer\Utils\DisableEventObserver;
use Magento\Framework\Event\Config;
use Magento\Framework\Event\Config\Data;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;

class DisableEventObserverTest extends TestCase
{
    use ProphecyTrait;

    public function testDisableEventObserverDisablesObserverIfRegistered(): void
    {
        $disableEventObserver = new DisableEventObserver();
        $disableEventObserver->disable('my-event', 'my-observer');

        $container = $this->prophesize(Data::class);

        $eventConfig = new Config($container->reveal());

        $result = $disableEventObserver->afterGetObservers($eventConfig, [['name' => 'my-observer']], 'my-event');

        self::assertEquals(['my-event' => ['my-observer']], $disableEventObserver->getDisabledObservers());
        self::assertEquals([['name' => 'my-observer', 'disabled' => true]], $result);
    }

    public function testNotRegisteredObserverIsNotDisabled(): void
    {
        $disableEventObserver = new DisableEventObserver();

        $container = $this->prophesize(Data::class);

        $eventConfig = new Config($container->reveal());

        $result = $disableEventObserver->afterGetObservers(
            $eventConfig,
            [['name' => 'my-observer']],
            'my-event'
        );

        self::assertEquals([], $disableEventObserver->getDisabledObservers());
        self::assertEquals([['name' => 'my-observer']], $result);
    }

    public function testDisableEventObserverOnlyDisablesRegisteredObserverAndIgnoresOthers(): void
    {
        $disableEventObserver = new DisableEventObserver();
        $disableEventObserver->disable('my-event', 'my-observer');

        $container = $this->prophesize(Data::class);

        $eventConfig = new Config($container->reveal());

        $result = $disableEventObserver->afterGetObservers(
            $eventConfig,
            [
                ['name' => 'my-observer'],
                ['name' => 'my-other-observer']
            ],
            'my-event'
        );

        self::assertEquals(['my-event' => ['my-observer']], $disableEventObserver->getDisabledObservers());
        self::assertEquals(
            [
                ['name' => 'my-observer', 'disabled' => true],
                ['name' => 'my-other-observer'],
            ],
            $result
        );
    }
}
