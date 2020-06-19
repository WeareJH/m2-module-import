<?php

declare(strict_types=1);

namespace Jh\Import\Writer\Utils;

use Magento\Framework\Event\Config;

class DisableEventObserver
{
    private $observersToDisable = [];

    public function disable(string $event, string $observerName): void
    {
        if (!isset($this->observersToDisable[$event])) {
            $this->observersToDisable[$event] = [];
        }
        $this->observersToDisable[$event][] = $observerName;
    }

    public function afterGetObservers(Config $eventConfig, array $observers, string $eventName): array
    {
        if (!isset($this->observersToDisable[$eventName])) {
            return $observers;
        }

        foreach ($this->observersToDisable[$eventName] as $observerName) {
            foreach ($observers as $i => $observerConfig) {
                if ($observerConfig['name'] === $observerName) {
                    $observers[$i]['disabled'] = true;
                }
            }
        }

        return $observers;
    }

    public function getDisabledObservers(): array
    {
        return $this->observersToDisable;
    }
}
