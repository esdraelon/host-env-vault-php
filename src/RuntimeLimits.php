<?php

declare(strict_types=1);

namespace Amtgard\HostEnvVault;

/**
 * Protocol size limits mirrored from host-env-vault core slots (Value Object).
 */
final class RuntimeLimits
{
    public const int ENTRY_NAME_MAX_BYTES = 64;

    public const int SECRET_MAX_BYTES = 4096;

    private function __construct()
    {
    }
}
