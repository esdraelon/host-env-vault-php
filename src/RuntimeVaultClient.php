<?php

declare(strict_types=1);

namespace Amtgard\HostEnvVault;

use Amtgard\HostEnvVault\Adapter\SocketExtensionRuntimeTransport;
use Amtgard\HostEnvVault\Exception\RuntimeFetchDeniedException;
use Amtgard\HostEnvVault\Port\RuntimeTransport;
use Amtgard\Traits\Builder\Builder;
use Amtgard\Traits\Builder\Getter;
use Optional\Optional;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

/**
 * Fetches runtime-listed secrets from a host-env-vault serve socket (Facade).
 */
final class RuntimeVaultClient
{
    use Builder;
    use Getter;

    protected string $socketPath = '';

    protected ?RuntimeTransport $transport = null;

    protected ?LoggerInterface $logger = null;

    protected RuntimeFrameCodec $codec;

    public function __construct()
    {
        $this->codec = new RuntimeFrameCodec();
    }

    public function socketPath(string $path): self
    {
        $this->socketPath = $path;

        return $this;
    }

    public function forRuntimeProfile(string $runtimeDir, string $profile): self
    {
        return $this->socketPath(RuntimeSocketPath::forProfile($runtimeDir, $profile)->path());
    }

    public function transport(RuntimeTransport $transport): self
    {
        $this->transport = $transport;

        return $this;
    }

    public function logger(LoggerInterface $logger): self
    {
        $this->logger = $logger;

        return $this;
    }

    public function fetchEnv(string $entry): string
    {
        return $this->fetch(RuntimeFetchKind::Env, $entry);
    }

    public function fetchFile(string $entry): string
    {
        return $this->fetch(RuntimeFetchKind::File, $entry);
    }

    public function fetch(RuntimeFetchKind $kind, string $entry): string
    {
        $transport = $this->resolveTransport();
        $request = $this->codec->encodeRequest($kind->opcode(), $entry);
        $wire = $transport->exchange($request);
        $response = $this->codec->decodeResponse($wire);

        if ($response['status'] === RuntimeResponseStatus::Denied) {
            Optional::ofNullable($this->logger)->ifPresent(
                function (LoggerInterface $logger) use ($entry, $kind): void {
                    $logger->log(
                        LogLevel::INFO,
                        'host-env-vault runtime fetch denied',
                        ['entry' => $entry, 'kind' => $kind->name],
                    );
                },
            );

            throw RuntimeFetchDeniedException::forEntry($entry);
        }

        Optional::ofNullable($this->logger)->ifPresent(
            function (LoggerInterface $logger) use ($entry, $kind): void {
                $logger->log(
                    LogLevel::DEBUG,
                    'host-env-vault runtime fetch ok',
                    ['entry' => $entry, 'kind' => $kind->name],
                );
            },
        );

        return $response['body'];
    }

    private function resolveTransport(): RuntimeTransport
    {
        return Optional::ofNullable($this->transport)
            ->orElseGet(function (): RuntimeTransport {
                if ($this->socketPath === '') {
                    throw new \LogicException('Set socketPath or inject a RuntimeTransport.');
                }

                return new SocketExtensionRuntimeTransport($this->socketPath);
            });
    }
}
