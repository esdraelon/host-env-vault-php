<?php

declare(strict_types=1);

namespace Amtgard\HostEnvVault\Tests\Support;

use Amtgard\HostEnvVault\RuntimeResponseStatus;

/**
 * Builds raw runtime frames for tests (Object Mother).
 */
final class RuntimeWire
{
    private function __construct()
    {
    }

    public static function okResponse(string $body): string
    {
        return chr(RuntimeResponseStatus::Ok->value) . pack('V', strlen($body)) . $body;
    }

    public static function deniedResponse(): string
    {
        return chr(RuntimeResponseStatus::Denied->value) . pack('V', 0);
    }
}
