<?php

declare(strict_types=1);

namespace Amtgard\HostEnvVault;

use Amtgard\HostEnvVault\Exception\RuntimeProtocolException;

/**
 * Encodes and decodes host-env-vault runtime frames (Strategy).
 */
final class RuntimeFrameCodec
{
    public function encodeRequest(RuntimeOpcode $opcode, string $entryName): string
    {
        $body = $entryName;
        $length = strlen($body);

        if ($length > RuntimeLimits::ENTRY_NAME_MAX_BYTES) {
            throw RuntimeProtocolException::entryNameTooLong(RuntimeLimits::ENTRY_NAME_MAX_BYTES);
        }

        return chr($opcode->value) . pack('V', $length) . $body;
    }

    /**
     * @return array{status: RuntimeResponseStatus, body: string}
     */
    public function decodeResponse(string $wire): array
    {
        if (strlen($wire) < 5) {
            throw RuntimeProtocolException::shortHeader(strlen($wire));
        }

        $statusByte = ord($wire[0]);
        $status = RuntimeResponseStatus::tryFrom($statusByte)
            ?? throw RuntimeProtocolException::unknownStatus($statusByte);

        $length = unpack('V', substr($wire, 1, 4))[1];
        if ($length > RuntimeLimits::SECRET_MAX_BYTES) {
            throw RuntimeProtocolException::secretTooLong(RuntimeLimits::SECRET_MAX_BYTES);
        }

        $body = substr($wire, 5);
        if (strlen($body) !== $length) {
            throw RuntimeProtocolException::shortHeader(strlen($wire));
        }

        return ['status' => $status, 'body' => $body];
    }
}
