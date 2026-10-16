<?php

declare(strict_types=1);

namespace Amtgard\HostEnvVault\Exception;

/**
 * Malformed or truncated runtime wire data (Exception Object).
 */
final class RuntimeProtocolException extends \RuntimeException
{
    public static function shortHeader(int $received): self
    {
        return new self(sprintf('Runtime response header too short (%d bytes).', $received));
    }

    public static function entryNameTooLong(int $length): self
    {
        return new self(sprintf('Runtime entry name exceeds %d bytes.', $length));
    }

    public static function secretTooLong(int $length): self
    {
        return new self(sprintf('Runtime secret exceeds %d bytes.', $length));
    }

    public static function socket(string $message): self
    {
        return new self('Unix socket error: ' . $message);
    }

    public static function unknownStatus(int $status): self
    {
        return new self(sprintf('Unknown runtime response status: %d.', $status));
    }
}
