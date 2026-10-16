<?php

declare(strict_types=1);

namespace Amtgard\HostEnvVault\Port;

/**
 * Sends one request frame and returns the raw response bytes (Port).
 */
interface RuntimeTransport
{
    public function exchange(string $requestFrame): string;
}
