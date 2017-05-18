<?php

namespace Jh\Import\Transformer;

use Jh\Import\Import\Record;
use Jh\Import\Report\ReportItem;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Framework\Phrase;

/**
 * @author Aydin Hassan <aydin@wearejh.com>
 */
class ProductVisibilityTransformer
{
    /**
     * @var string
     */
    private $column;

    /**
     * @var array
     */
    private $options;

    /**
     * @var int|null
     */
    private $defaultVisibilityId;

    public function __construct(string $column, int $defaultVisibilityId = null)
    {
        $this->column  = $column;
        $this->options = collect(Visibility::getOptionArray())
            ->map(function (Phrase $phrase) {
                return $phrase->render();
            })
            ->flip()
            ->toArray();

        if ($defaultVisibilityId && !in_array($defaultVisibilityId, $this->options, true)) {
            throw new \InvalidArgumentException(sprintf('Invalid default visibility: "%s"', $defaultVisibilityId));
        }
        $this->defaultVisibilityId = $defaultVisibilityId;
    }

    public function __invoke(Record $record, ReportItem $reportItem)
    {
        $record->transform($this->column, function ($visibility) use ($reportItem) {
            if (!$visibility && $this->defaultVisibilityId) {
                return $this->defaultVisibilityId;
            }

            if (isset($this->options[$visibility])) {
                return $this->options[$visibility];
            }

            $reportItem->addWarning(sprintf('Product Visibility "%s" is invalid and/or not supported.', $visibility));
            return null;
        });
    }
}
