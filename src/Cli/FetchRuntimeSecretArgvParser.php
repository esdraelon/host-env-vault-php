<?php

declare(strict_types=1);

namespace Amtgard\HostEnvVault\Cli;

/**
 * Parses `fetch-runtime-secret` argv into {@see FetchRuntimeSecretOptions} (Parser).
 */
final class FetchRuntimeSecretArgvParser
{
    public const int EXIT_OK = 0;

    public const int EXIT_USAGE = 64;

    /**
     * @param list<string> $argv
     */
    public function parse(array $argv): FetchRuntimeSecretOptions
    {
        $args = array_slice($argv, 1);
        $entry = null;
        $socketPath = null;
        $runtimeDir = null;
        $profile = null;
        $fileEntry = false;

        for ($i = 0; $i < count($args); ++$i) {
            $arg = $args[$i];
            match ($arg) {
                '--help', '-h' => throw new FetchRuntimeSecretUsageException($this->usage()),
                '--file' => $fileEntry = true,
                '--entry' => $entry = $this->requireValue($args, $i, '--entry'),
                '--socket' => $socketPath = $this->requireValue($args, $i, '--socket'),
                '--runtime-dir' => $runtimeDir = $this->requireValue($args, $i, '--runtime-dir'),
                '--profile' => $profile = $this->requireValue($args, $i, '--profile'),
                default => throw new FetchRuntimeSecretUsageException(
                    sprintf("Unknown argument %s.\n\n%s", $arg, $this->usage()),
                ),
            };
        }

        if ($entry === null || $entry === '') {
            throw new FetchRuntimeSecretUsageException("Missing required --entry.\n\n" . $this->usage());
        }

        $hasSocket = $socketPath !== null && $socketPath !== '';
        $hasRuntimePair = ($runtimeDir !== null && $runtimeDir !== '')
            || ($profile !== null && $profile !== '');

        if ($hasSocket && $hasRuntimePair) {
            throw new FetchRuntimeSecretUsageException(
                "Use either --socket or (--runtime-dir with --profile), not both.\n\n" . $this->usage(),
            );
        }

        if (!$hasSocket) {
            if ($runtimeDir === null || $runtimeDir === '' || $profile === null || $profile === '') {
                throw new FetchRuntimeSecretUsageException(
                    "Provide --socket or both --runtime-dir and --profile.\n\n" . $this->usage(),
                );
            }
        }

        return new FetchRuntimeSecretOptions(
            $entry,
            $fileEntry,
            $hasSocket ? $socketPath : null,
            $hasSocket ? null : $runtimeDir,
            $hasSocket ? null : $profile,
        );
    }

    public function usage(): string
    {
        return <<<'TEXT'
Usage:
  fetch-runtime-secret --entry NAME (--socket PATH | --runtime-dir DIR --profile NAME) [--file]

Options:
  --entry         Runtime catalog entry name (required)
  --socket        Unix socket path (alternative to runtime-dir + profile)
  --runtime-dir   host-env-vault runtime directory (e.g. /run/host-env-vault)
  --profile       Profile name for socket under runtime-dir/sockets/
  --file          Fetch a runtime_files entry instead of runtime env
  -h, --help      Show this help

Writes secret bytes to stdout. Errors go to stderr.
TEXT;
    }

    /**
     * @param list<string> $args
     */
    private function requireValue(array $args, int &$index, string $flag): string
    {
        if (!isset($args[$index + 1])) {
            throw new FetchRuntimeSecretUsageException(
                sprintf('Missing value for %s.\n\n%s', $flag, $this->usage()),
            );
        }

        ++$index;

        return $args[$index];
    }
}
