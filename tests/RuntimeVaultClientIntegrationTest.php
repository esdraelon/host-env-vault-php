<?php

declare(strict_types=1);

namespace Amtgard\HostEnvVault\Tests;

use Amtgard\HostEnvVault\RuntimeVaultClient;
use Amtgard\HostEnvVault\Tests\Support\RuntimeWire;
use PHPUnit\Framework\TestCase;

final class RuntimeVaultClientIntegrationTest extends TestCase
{
    public function testFetchEnvUsesDefaultSocketTransport(): void
    {
        if (!function_exists('pcntl_fork')) {
            $this->markTestSkipped('pcntl extension required for unix socket integration test.');
        }

        $path = sys_get_temp_dir() . '/hev-php-integ-' . uniqid('', true) . '.sock';
        @unlink($path);

        $server = socket_create(AF_UNIX, SOCK_STREAM, 0);
        if ($server === false || !socket_bind($server, $path) || !socket_listen($server, 1)) {
            $this->markTestSkipped('Could not bind unix server socket.');
        }

        $pid = pcntl_fork();
        if ($pid === 0) {
            $peer = socket_accept($server);
            socket_read($peer, 4096);
            socket_write($peer, RuntimeWire::okResponse('live-secret'));
            socket_close($peer);
            socket_close($server);
            exit(0);
        }

        $secret = RuntimeVaultClient::builder()
            ->build()
            ->socketPath($path)
            ->fetchEnv('API_SECRET');

        pcntl_waitpid($pid, $status);
        socket_close($server);
        @unlink($path);

        $this->assertSame(0, pcntl_wexitstatus($status));
        $this->assertSame('live-secret', $secret);
    }
}
