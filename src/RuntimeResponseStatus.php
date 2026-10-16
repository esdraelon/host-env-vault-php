<?php

declare(strict_types=1);

namespace Amtgard\HostEnvVault;

/**
 * Response status byte on the runtime socket (Type Object).
 */
enum RuntimeResponseStatus: int
{
    case Ok = 0;
    case Denied = 1;
}
