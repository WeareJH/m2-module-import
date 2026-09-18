<?php
declare(strict_types=1);

namespace Jh\Import\Writer;

use Magento\Framework\ObjectManagerInterface;
use RuntimeException;

use function sprintf;

class WriterFactory
{
    public function __construct(private readonly ObjectManagerInterface $objectManager)
    {
    }

    public function create(string $className): Writer
    {
        $writer = $this->objectManager->get($className);

        if (!$writer instanceof Writer) {
            throw new RuntimeException(sprintf('"%s" must implement "%s"', $className, Writer::class));
        }

        return $writer;
    }
}
