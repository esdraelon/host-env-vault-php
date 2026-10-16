<?php

declare(strict_types=1);

namespace Amtgard\HostEnvVault\Exception;

/**
 * host-env-vault returned ST_DENIED for the requested entry (Exception Object).
 */
final class RuntimeFetchDeniedException extends \RuntimeException
{
    public static function forEntry(string $entry): self
    {
        return new self(sprintf('Denied fetching runtime entry %s.', $entry));
    }
}
