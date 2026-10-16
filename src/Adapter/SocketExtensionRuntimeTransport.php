<?php

declare(strict_types=1);

namespace Amtgard\HostEnvVault\Adapter;

use Amtgard\HostEnvVault\Exception\RuntimeProtocolException;
use Amtgard\HostEnvVault\Port\RuntimeTransport;
use Amtgard\HostEnvVault\RuntimeSocketSession;

/**
 * One-shot AF_UNIX exchange via ext-sockets (Adapter).
 */
final class SocketExtensionRuntimeTransport implements RuntimeTransport
{
    /** @var null|callable(string): resource */
    private $streamFactory;

    /**
     * @param null|callable(string): resource $streamFactory overrides unix connect (tests)
     */
    public function __construct(
        private readonly string $socketPath,
        ?callable $streamFactory = null,
    ) {
        $this->streamFactory = $streamFactory;
    }

    public function exchange(string $requestFrame): string
    {
        $stream = $this->openStream($this->socketPath);

        try {
            return (new RuntimeSocketSession($stream))->exchange($requestFrame);
        } finally {
            fclose($stream);
        }
    }

    /** @return resource */
    private function openStream(string $socketPath)
    {
        if ($this->streamFactory !== null) {
            return ($this->streamFactory)($socketPath);
        }

        $socket = socket_create(AF_UNIX, SOCK_STREAM, 0);
        if ($socket === false) {
            throw RuntimeProtocolException::socket($this->lastSocketError());
        }

        if (!socket_connect($socket, $socketPath)) {
            socket_close($socket);
            throw RuntimeProtocolException::socket($this->lastSocketError());
        }

        $stream = socket_export_stream($socket);
        if ($stream === false) {
            socket_close($socket);
            throw RuntimeProtocolException::socket('socket_export_stream failed');
        }

        return $stream;
    }

    private function lastSocketError(): string
    {
        return socket_strerror(socket_last_error());
    }
}
