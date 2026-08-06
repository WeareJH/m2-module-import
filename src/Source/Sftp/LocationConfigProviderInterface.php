<?php
declare(strict_types=1);

namespace Jh\Import\Source\Sftp;

/**
 * The abstraction whatever supplies an SFTP-sourced feed's connection and location details
 * implements, without the entity import module that consumes it ever knowing where the feed
 * physically lives.
 *
 * Entity import modules reference an *unbound* virtual type name implementing this interface in
 * their `imports.xml` (`location_config_provider`); that name is bound via `di.xml`, either by the
 * entity module itself or by a separate adapter module if source-swap flexibility is needed. If
 * nothing binds it, resolving it throws - there is no silent fallback to a hardcoded default.
 */
interface LocationConfigProviderInterface
{
    public function getLocationConfig(): LocationConfig;
}
