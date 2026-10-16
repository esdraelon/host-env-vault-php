<?php

declare(strict_types=1);

namespace Amtgard\HostEnvVault\Tests\Cli;

use Amtgard\HostEnvVault\Cli\FetchRuntimeSecretArgvParser;
use Amtgard\HostEnvVault\Cli\FetchRuntimeSecretUsageException;
use PHPUnit\Framework\TestCase;

final class FetchRuntimeSecretArgvParserTest extends TestCase
{
    private FetchRuntimeSecretArgvParser $parser;

    protected function setUp(): void
    {
        $this->parser = new FetchRuntimeSecretArgvParser();
    }

    public function testParseAcceptsSocketAndEntry(): void
    {
        $options = $this->parser->parse([
            'fetch-runtime-secret',
            '--entry',
            'API_SECRET',
            '--socket',
            '/run/host-env-vault/sockets/worker.sock',
        ]);

        $this->assertSame('API_SECRET', $options->entry);
        $this->assertFalse($options->fileEntry);
        $this->assertSame('/run/host-env-vault/sockets/worker.sock', $options->socketPath);
    }

    public function testParseAcceptsRuntimeDirAndProfile(): void
    {
        $options = $this->parser->parse([
            'fetch-runtime-secret',
            '--entry',
            'KEY',
            '--runtime-dir',
            '/run/host-env-vault',
            '--profile',
            'app-worker',
            '--file',
        ]);

        $this->assertTrue($options->fileEntry);
        $this->assertSame('/run/host-env-vault', $options->runtimeDir);
        $this->assertSame('app-worker', $options->profile);
        $this->assertNull($options->socketPath);
    }

    public function testParseRejectsMissingEntry(): void
    {
        $this->expectException(FetchRuntimeSecretUsageException::class);

        $this->parser->parse(['fetch-runtime-secret', '--socket', '/tmp/x.sock']);
    }

    public function testParseRejectsBothSocketAndRuntimeDir(): void
    {
        $this->expectException(FetchRuntimeSecretUsageException::class);

        $this->parser->parse([
            'fetch-runtime-secret',
            '--entry',
            'X',
            '--socket',
            '/tmp/x.sock',
            '--runtime-dir',
            '/run',
            '--profile',
            'p',
        ]);
    }

    public function testHelpThrowsUsageException(): void
    {
        try {
            $this->parser->parse(['fetch-runtime-secret', '--help']);
            $this->fail('Expected FetchRuntimeSecretUsageException');
        } catch (FetchRuntimeSecretUsageException $e) {
            $this->assertStringContainsString('--entry', $e->getMessage());
        }
    }

    public function testParseRejectsUnknownArgument(): void
    {
        $this->expectException(FetchRuntimeSecretUsageException::class);

        $this->parser->parse([
            'fetch-runtime-secret',
            '--entry',
            'X',
            '--socket',
            '/tmp/x.sock',
            '--verbose',
        ]);
    }

    public function testParseRejectsMissingFlagValue(): void
    {
        $this->expectException(FetchRuntimeSecretUsageException::class);

        $this->parser->parse(['fetch-runtime-secret', '--entry']);
    }

    public function testParseRejectsPartialRuntimePair(): void
    {
        $this->expectException(FetchRuntimeSecretUsageException::class);

        $this->parser->parse([
            'fetch-runtime-secret',
            '--entry',
            'X',
            '--runtime-dir',
            '/run/host-env-vault',
        ]);
    }
}
