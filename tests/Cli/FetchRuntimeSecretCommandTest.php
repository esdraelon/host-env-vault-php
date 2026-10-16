<?php

declare(strict_types=1);

namespace Amtgard\HostEnvVault\Tests\Cli;

use Amtgard\HostEnvVault\Adapter\CallableRuntimeTransport;
use Amtgard\HostEnvVault\Cli\FetchRuntimeSecretCommand;
use Amtgard\HostEnvVault\Cli\FetchRuntimeSecretOptions;
use Amtgard\HostEnvVault\Tests\Support\RuntimeWire;
use PHPUnit\Framework\TestCase;

final class FetchRuntimeSecretCommandTest extends TestCase
{
    public function testRunFetchesViaRuntimeDirProfile(): void
    {
        $command = new FetchRuntimeSecretCommand(new FetchRuntimeSecretOptions(
            'API_SECRET',
            false,
            null,
            '/run/host-env-vault',
            'worker',
        ));

        $secret = $command->run(new CallableRuntimeTransport(
            static fn (): string => RuntimeWire::okResponse('cli-secret'),
        ));

        $this->assertSame('cli-secret', $secret);
    }

    public function testRunUsesSocketPathAndFileEntry(): void
    {
        $command = new FetchRuntimeSecretCommand(new FetchRuntimeSecretOptions(
            'signing.pem',
            true,
            '/var/run/host-env-vault.sock',
            null,
            null,
        ));

        $secret = $command->run(new CallableRuntimeTransport(
            static fn (string $frame): string => RuntimeWire::okResponse(
                str_contains($frame, 'signing.pem') ? 'pem-body' : '',
            ),
        ));

        $this->assertSame('pem-body', $secret);
    }
}
