<?php
declare(strict_types=1);

namespace Jh\Import\Specification;

use Magento\Framework\ObjectManagerInterface;
use RuntimeException;

use function sprintf;

class SpecificationFactory
{
    public function __construct(private readonly ObjectManagerInterface $objectManager)
    {
    }

    public function create(string $className): ImportSpecification
    {
        $specification = $this->objectManager->get($className);

        if (!$specification instanceof ImportSpecification) {
            throw new RuntimeException(
                sprintf('"%s" must implement "%s"', $className, ImportSpecification::class)
            );
        }

        return $specification;
    }
}
