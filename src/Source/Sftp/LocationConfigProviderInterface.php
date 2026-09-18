<?php
declare(strict_types=1);

namespace Jh\Import\Source\Sftp;

/**
 * Supplies an SFTP-sourced feed's connection and location details, so the entity import module
 * consuming it never has to know where the feed actually lives.
 *
 * Entity modules reference an unbound virtual type name implementing this in their `imports.xml`
 * (`location_config_provider`); di.xml binds that name to a real implementation, either in the
 * entity module itself or a separate adapter module if you need source-swap flexibility. Nothing
 * bound means resolving it throws - there's no silent fallback to a hardcoded default.
 */
interface LocationConfigProviderInterface
{
    public function getLocationConfig(): LocationConfig;
}
