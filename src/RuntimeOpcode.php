<?php

declare(strict_types=1);

namespace Amtgard\HostEnvVault;

/**
 * Wire opcodes for host-env-vault runtime frames (Type Object).
 */
enum RuntimeOpcode: int
{
    case FetchEnv = 1;
    case FetchFile = 2;
}
