<?php

declare(strict_types=1);

namespace Amtgard\HostEnvVault\Tests;

use Amtgard\HostEnvVault\Adapter\CallableRuntimeTransport;
use Amtgard\HostEnvVault\Exception\RuntimeFetchDeniedException;
use Amtgard\HostEnvVault\RuntimeFetchKind;
use Amtgard\HostEnvVault\RuntimeFrameCodec;
use Amtgard\HostEnvVault\RuntimeVaultClient;
use Amtgard\HostEnvVault\Tests\Support\RuntimeWire;
use Monolog\Handler\TestHandler;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;

final class RuntimeVaultClientTest extends TestCase
{
    public function testFetchEnvReturnsSecretBody(): void
    {
        $codec = new RuntimeFrameCodec();
        $client = RuntimeVaultClient::builder()
            ->build()
            ->transport(new CallableRuntimeTransport(
                function (string $frame) use ($codec): string {
                    $this->assertStringStartsWith("\x01", $frame);

                    return RuntimeWire::okResponse('top-secret');
                },
            ));

        $this->assertSame('top-secret', $client->fetchEnv('API_SECRET'));
    }

    public function testFetchFileUsesFileOpcode(): void
    {
        $client = RuntimeVaultClient::builder()
            ->build()
            ->transport(new CallableRuntimeTransport(
                function (string $frame): string {
                    $this->assertSame("\x02", $frame[0]);

                    return RuntimeWire::okResponse('pem-bytes');
                },
            ));

        $this->assertSame('pem-bytes', $client->fetchFile('signing.pem'));
    }

    public function testFetchDeniedThrowsAndLogsInfo(): void
    {
        $handler = new TestHandler();
        $logger = new Logger('test');
        $logger->pushHandler($handler);

        $client = RuntimeVaultClient::builder()
            ->build()
            ->logger($logger)
            ->transport(new CallableRuntimeTransport(
                static fn (): string => RuntimeWire::deniedResponse(),
            ));

        try {
            $client->fetch(RuntimeFetchKind::Env, 'NOPE');
            $this->fail('Expected RuntimeFetchDeniedException');
        } catch (RuntimeFetchDeniedException) {
        }

        $this->assertTrue($handler->hasInfoRecords());
        $this->assertStringContainsString('denied', $handler->getRecords()[0]['message']);
    }

    public function testFetchOkLogsDebug(): void
    {
        $handler = new TestHandler();
        $logger = new Logger('test');
        $logger->pushHandler($handler);

        $client = RuntimeVaultClient::builder()
            ->build()
            ->logger($logger)
            ->transport(new CallableRuntimeTransport(
                static fn (): string => RuntimeWire::okResponse('x'),
            ));

        $client->fetchEnv('KEY');

        $this->assertTrue($handler->hasDebugRecords());
    }

    public function testForRuntimeProfileSetsSocketPath(): void
    {
        $client = RuntimeVaultClient::builder()
            ->build()
            ->forRuntimeProfile('/run/host-env-vault', 'worker');

        $this->assertSame(
            '/run/host-env-vault/sockets/worker.sock',
            $client->getSocketPath(),
        );
    }

    public function testSocketPathOverridesPreviousProfile(): void
    {
        $client = RuntimeVaultClient::builder()
            ->build()
            ->forRuntimeProfile('/run/host-env-vault', 'worker')
            ->socketPath('/custom.sock');

        $this->assertSame('/custom.sock', $client->getSocketPath());
    }

    public function testResolveTransportRequiresPathWhenNoInjectedTransport(): void
    {
        $client = RuntimeVaultClient::builder()->build();

        $this->expectException(\LogicException::class);

        $client->fetchEnv('KEY');
    }
}
