<?php

declare(strict_types=1);

namespace Amtgard\HostEnvVault\Cli;

/**
 * Parsed arguments for the fetch-runtime-secret CLI (Value Object).
 */
final readonly class FetchRuntimeSecretOptions
{
    public function __construct(
        public string $entry,
        public bool $fileEntry,
        public ?string $socketPath,
        public ?string $runtimeDir,
        public ?string $profile,
    ) {
    }

    public function usesSocketPath(): bool
    {
        return $this->socketPath !== null;
    }
}
