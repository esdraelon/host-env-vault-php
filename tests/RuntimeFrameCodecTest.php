<?php

declare(strict_types=1);

namespace Amtgard\HostEnvVault\Tests;

use Amtgard\HostEnvVault\Exception\RuntimeProtocolException;
use Amtgard\HostEnvVault\RuntimeFetchKind;
use Amtgard\HostEnvVault\RuntimeFrameCodec;
use Amtgard\HostEnvVault\RuntimeLimits;
use Amtgard\HostEnvVault\RuntimeResponseStatus;
use Amtgard\HostEnvVault\Tests\Support\RuntimeWire;
use PHPUnit\Framework\TestCase;

final class RuntimeFrameCodecTest extends TestCase
{
    private RuntimeFrameCodec $codec;

    protected function setUp(): void
    {
        $this->codec = new RuntimeFrameCodec();
    }

    public function testEncodeRequestUsesLittleEndianLength(): void
    {
        $wire = $this->codec->encodeRequest(RuntimeFetchKind::Env->opcode(), 'APP_KEY');

        $this->assertSame("\x01", $wire[0]);
        $this->assertSame(7, unpack('V', substr($wire, 1, 4))[1]);
        $this->assertSame('APP_KEY', substr($wire, 5));
    }

    public function testDecodeResponseReturnsOkBody(): void
    {
        $decoded = $this->codec->decodeResponse(RuntimeWire::okResponse('secret-bytes'));

        $this->assertSame(RuntimeResponseStatus::Ok, $decoded['status']);
        $this->assertSame('secret-bytes', $decoded['body']);
    }

    public function testEncodeRejectsOversizeEntryName(): void
    {
        $this->expectException(RuntimeProtocolException::class);

        $this->codec->encodeRequest(
            RuntimeFetchKind::File->opcode(),
            str_repeat('x', RuntimeLimits::ENTRY_NAME_MAX_BYTES + 1),
        );
    }

    public function testDecodeRejectsShortWire(): void
    {
        $this->expectException(RuntimeProtocolException::class);

        $this->codec->decodeResponse("\x00\x01");
    }

    public function testDecodeRejectsOversizeSecretLength(): void
    {
        $header = chr(0) . pack('V', RuntimeLimits::SECRET_MAX_BYTES + 1);

        $this->expectException(RuntimeProtocolException::class);

        $this->codec->decodeResponse($header);
    }

    public function testDecodeRejectsUnknownStatus(): void
    {
        $wire = chr(9) . pack('V', 0);

        $this->expectException(RuntimeProtocolException::class);

        $this->codec->decodeResponse($wire);
    }

    public function testDecodeRejectsTruncatedBody(): void
    {
        $wire = chr(0) . pack('V', 4) . 'ab';

        $this->expectException(RuntimeProtocolException::class);

        $this->codec->decodeResponse($wire);
    }
}
