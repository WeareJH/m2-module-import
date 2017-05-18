<?php

namespace Jh\Import\Transformer;

use Jh\Import\Import\Record;
use Jh\Import\Report\ReportItem;
use Magento\Catalog\Api\Data\CategoryTreeInterface;
use Magento\Catalog\Model\CategoryManagement;

/**
 * @author Aydin Hassan <aydin@wearejh.com>
 */
class CategoryPathTransformer
{

    /**
     * @var \Magento\Catalog\Api\Data\CategoryTreeInterface
     */
    private $tree;

    public function __construct(CategoryManagement $categoryManagement)
    {
        $this->tree = $categoryManagement->getTree();
    }

    public function __invoke(Record $record, ReportItem $reportItem)
    {
        $record->transform('categories', function ($categories) use ($reportItem) {
            return collect($categories)
                ->map(function ($categoryPath) use ($reportItem) {
                    $parts      = explode('/', $categoryPath);
                    $first      = array_shift($parts);
                    return $this->findCategory($this->tree, $first, $parts, $reportItem);
                })
                ->filter()
                ->toArray();
        });
    }

    public function findCategory(
        CategoryTreeInterface $categoryTree,
        $categoryName,
        array $parts,
        ReportItem $reportItem
    ) {
        foreach ($categoryTree->getChildrenData() as $tree) {
            if ($categoryName === $tree->getName()) {
                if (empty($parts)) {
                    return $tree->getId();
                }

                $next = array_shift($parts);
                return $this->findCategory($tree, $next, $parts, $reportItem);
            }
        }

        $reportItem->addWarning(sprintf('Category: "%s" does not exist.', $categoryName));
        return null;
    }
}
