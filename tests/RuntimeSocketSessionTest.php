<?php

declare(strict_types=1);

namespace Amtgard\HostEnvVault\Tests;

use Amtgard\HostEnvVault\Exception\RuntimeProtocolException;
use Amtgard\HostEnvVault\RuntimeFrameCodec;
use Amtgard\HostEnvVault\RuntimeOpcode;
use Amtgard\HostEnvVault\RuntimeSocketSession;
use Amtgard\HostEnvVault\Tests\Support\RuntimeWire;
use PHPUnit\Framework\TestCase;

final class RuntimeSocketSessionTest extends TestCase
{
    /** @var array{0: resource, 1: resource} */
    private array $pair;

    protected function setUp(): void
    {
        $pair = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);
        if ($pair === false) {
            $this->markTestSkipped('stream_socket_pair unavailable');
        }

        $this->pair = $pair;
    }

    protected function tearDown(): void
    {
        foreach ($this->pair as $stream) {
            if (is_resource($stream)) {
                fclose($stream);
            }
        }
    }

    public function testExchangeRoundTripsOnConnectedPair(): void
    {
        if (!function_exists('pcntl_fork')) {
            $this->markTestSkipped('pcntl extension required for exchange coverage.');
        }

        [$client, $server] = $this->pair;
        $codec = new RuntimeFrameCodec();
        $request = $codec->encodeRequest(RuntimeOpcode::FetchEnv, 'APP_KEY');

        $pid = pcntl_fork();
        if ($pid === 0) {
            fread($server, 4096);
            fwrite($server, RuntimeWire::okResponse('vault-value'));
            exit(0);
        }

        $decoded = $codec->decodeResponse((new RuntimeSocketSession($client))->exchange($request));
        pcntl_waitpid($pid, $status);

        $this->assertSame(0, pcntl_wexitstatus($status));
        $this->assertSame('vault-value', $decoded['body']);
    }

    public function testReceiveFailsWhenPeerClosesEarly(): void
    {
        [$client, $server] = $this->pair;
        fclose($server);

        $this->expectException(RuntimeProtocolException::class);

        (new RuntimeSocketSession($client))->receive();
    }

    public function testReceiveFailsWhenBodyTruncated(): void
    {
        [$client, $server] = $this->pair;
        $session = new RuntimeSocketSession($client);

        $session->transmit("\x01\x01\x00\x00\x00X");
        fread($server, 4096);
        fwrite($server, chr(0) . pack('V', 12) . 'too-short');
        fclose($server);

        $this->expectException(RuntimeProtocolException::class);

        $session->receive();
    }

    public function testReceiveReadsLargeBodyInMultipleChunks(): void
    {
        [$client, $server] = $this->pair;
        $session = new RuntimeSocketSession($client);
        $body = str_repeat('z', 512);

        $session->transmit("\x01\x01\x00\x00\x00K");
        fread($server, 4096);
        fwrite($server, RuntimeWire::okResponse($body));

        $decoded = (new RuntimeFrameCodec())->decodeResponse($session->receive());
        $this->assertSame($body, $decoded['body']);
    }
}
