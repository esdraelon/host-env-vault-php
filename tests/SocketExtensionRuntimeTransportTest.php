<?php

declare(strict_types=1);

namespace Amtgard\HostEnvVault\Tests;

use Amtgard\HostEnvVault\Adapter\SocketExtensionRuntimeTransport;
use Amtgard\HostEnvVault\Exception\RuntimeProtocolException;
use Amtgard\HostEnvVault\RuntimeFrameCodec;
use Amtgard\HostEnvVault\RuntimeOpcode;
use Amtgard\HostEnvVault\Tests\Support\RuntimeWire;
use PHPUnit\Framework\TestCase;

final class SocketExtensionRuntimeTransportTest extends TestCase
{
    public function testExchangeFailsForMissingSocketPath(): void
    {
        $transport = new SocketExtensionRuntimeTransport('/tmp/hev-missing-' . uniqid('', true) . '.sock');

        $this->expectException(RuntimeProtocolException::class);

        $transport->exchange("\x01\x01\x00\x00\x00X");
    }

    public function testExchangeRoundTripsAgainstBoundSocket(): void
    {
        if (!function_exists('pcntl_fork')) {
            $this->markTestSkipped('pcntl extension required for unix socket integration test.');
        }

        $path = sys_get_temp_dir() . '/hev-php-bind-' . uniqid('', true) . '.sock';
        @unlink($path);

        $server = socket_create(AF_UNIX, SOCK_STREAM, 0);
        $this->assertInstanceOf(\Socket::class, $server);
        socket_bind($server, $path);
        socket_listen($server, 1);

        $codec = new RuntimeFrameCodec();
        $request = $codec->encodeRequest(RuntimeOpcode::FetchEnv, 'APP_KEY');

        $pid = pcntl_fork();
        if ($pid === 0) {
            $peer = socket_accept($server);
            socket_read($peer, 4096);
            socket_write($peer, RuntimeWire::okResponse('from-vault'));
            socket_close($peer);
            socket_close($server);
            exit(0);
        }

        $transport = new SocketExtensionRuntimeTransport($path);
        $decoded = $codec->decodeResponse($transport->exchange($request));

        pcntl_waitpid($pid, $status);
        @unlink($path);

        $this->assertSame(0, pcntl_wexitstatus($status));
        $this->assertSame('from-vault', $decoded['body']);
    }

    public function testExchangeUsesInjectedStreamFactory(): void
    {
        if (!function_exists('pcntl_fork')) {
            $this->markTestSkipped('pcntl extension required for stream factory test.');
        }

        $pair = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);
        if ($pair === false) {
            $this->markTestSkipped('stream_socket_pair unavailable');
        }

        [$client, $server] = $pair;
        $codec = new RuntimeFrameCodec();
        $request = $codec->encodeRequest(RuntimeOpcode::FetchEnv, 'KEY');

        $pid = pcntl_fork();
        if ($pid === 0) {
            fread($server, 4096);
            fwrite($server, RuntimeWire::okResponse('injected'));
            exit(0);
        }

        $transport = new SocketExtensionRuntimeTransport('unused', static fn () => $client);
        $decoded = $codec->decodeResponse($transport->exchange($request));
        pcntl_waitpid($pid, $status);

        $this->assertSame(0, pcntl_wexitstatus($status));
        $this->assertSame('injected', $decoded['body']);
    }
}
