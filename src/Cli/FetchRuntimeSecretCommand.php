<?php

declare(strict_types=1);

namespace Amtgard\HostEnvVault\Cli;

use Amtgard\HostEnvVault\Port\RuntimeTransport;
use Amtgard\HostEnvVault\RuntimeVaultClient;

/**
 * Runs one runtime secret fetch for the CLI (Command).
 */
final class FetchRuntimeSecretCommand
{
    public function __construct(private readonly FetchRuntimeSecretOptions $options)
    {
    }

    public function run(?RuntimeTransport $transport = null): string
    {
        $client = RuntimeVaultClient::builder()->build();

        if ($this->options->usesSocketPath()) {
            $client->socketPath($this->options->socketPath);
        } else {
            $client->forRuntimeProfile($this->options->runtimeDir, $this->options->profile);
        }

        if ($transport !== null) {
            $client->transport($transport);
        }

        if ($this->options->fileEntry) {
            return $client->fetchFile($this->options->entry);
        }

        return $client->fetchEnv($this->options->entry);
    }
}
