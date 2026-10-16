<?php

declare(strict_types=1);

namespace Amtgard\HostEnvVault;

use Amtgard\HostEnvVault\Exception\RuntimeProtocolException;

/**
 * Request/response exchange on an already-connected unix stream (Template Method).
 */
final class RuntimeSocketSession
{
    /** @param resource $stream */
    public function __construct(private $stream)
    {
    }

    public function exchange(string $requestFrame): string
    {
        $this->transmit($requestFrame);

        return $this->receive();
    }

    public function transmit(string $requestFrame): void
    {
        $this->writeAll($requestFrame);
    }

    public function receive(): string
    {
        return $this->readResponse();
    }

    private function writeAll(string $data): void
    {
        $offset = 0;
        $total = strlen($data);

        while ($offset < $total) {
            $written = fwrite($this->stream, substr($data, $offset), $total - $offset);
            if ($written === false) {
                throw RuntimeProtocolException::socket('fwrite failed');
            }

            $offset += $written;
        }
    }

    private function readResponse(): string
    {
        $header = $this->readExact(5);
        $length = unpack('V', substr($header, 1, 4))[1];

        return $header . $this->readExact($length);
    }

    private function readExact(int $length): string
    {
        $buffer = '';

        while (strlen($buffer) < $length) {
            $chunk = fread($this->stream, $length - strlen($buffer));
            if ($chunk === false || $chunk === '') {
                throw RuntimeProtocolException::shortHeader(strlen($buffer));
            }

            $buffer .= $chunk;
        }

        return $buffer;
    }
}
