<?php

declare(strict_types=1);

namespace Amtgard\HostEnvVault;

/**
 * Canonical `{runtime_dir}/sockets/{profile}.sock` path (Value Object).
 */
final readonly class RuntimeSocketPath
{
    private function __construct(private string $path)
    {
    }

    public static function forProfile(string $runtimeDir, string $profile): self
    {
        $normalized = rtrim($runtimeDir, '/');
        $profile = trim($profile);

        if ($profile === '') {
            throw new \InvalidArgumentException('Profile name must not be empty.');
        }

        return new self(sprintf('%s/sockets/%s.sock', $normalized, $profile));
    }

    public function path(): string
    {
        return $this->path;
    }
}
