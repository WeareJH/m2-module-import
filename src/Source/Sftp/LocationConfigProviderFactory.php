<?php
declare(strict_types=1);

namespace Jh\Import\Source\Sftp;

use Magento\Framework\ObjectManagerInterface;
use RuntimeException;

use function sprintf;

/**
 * Resolves a `location_config_provider` class name (whatever an entity's imports.xml names) into
 * a LocationConfigProviderInterface, so SftpFiles doesn't have to depend on ObjectManager
 * directly.
 */
class LocationConfigProviderFactory
{
    public function __construct(private readonly ObjectManagerInterface $objectManager)
    {
    }

    public function create(string $className): LocationConfigProviderInterface
    {
        $provider = $this->objectManager->get($className);

        if (!$provider instanceof LocationConfigProviderInterface) {
            throw new RuntimeException(
                sprintf('"%s" must implement "%s"', $className, LocationConfigProviderInterface::class)
            );
        }

        return $provider;
    }
}
