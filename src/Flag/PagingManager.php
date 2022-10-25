<?php

declare(strict_types=1);

namespace Jh\Import\Flag;

use Magento\Framework\FlagManager;

class PagingManager
{
    private const PAGING_FLAG = 'import-page';

    private ?string $flagSuffix;
    private FlagManager $flagManager;

    public function __construct(
        FlagManager $flagManager,
        ?string $flagSuffix = null
    ) {
        $this->flagManager = $flagManager;
        $this->flagSuffix = $flagSuffix;
    }

    public function reset(): void
    {
        $this->flagManager->deleteFlag($this->computeFlagName());
    }

    public function setValue(int $page): void
    {
        $this->flagManager->saveFlag($this->computeFlagName(), $page);
    }

    public function getValue(): ?int
    {
        return $this->flagManager->getFlagData($this->computeFlagName());
    }

    private function computeFlagName(): string
    {
        if (!empty($this->flagSuffix)) {
            return sprintf('%s-%s', self::PAGING_FLAG, $this->flagSuffix);
        }

        return self::PAGING_FLAG;
    }
}