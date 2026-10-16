<?php

declare(strict_types=1);

namespace Amtgard\HostEnvVault\Tests;

use Amtgard\HostEnvVault\RuntimeSocketPath;
use PHPUnit\Framework\TestCase;

final class RuntimeSocketPathTest extends TestCase
{
    public function testForProfileBuildsCanonicalPath(): void
    {
        $path = RuntimeSocketPath::forProfile('/run/host-env-vault', 'app-worker');

        $this->assertSame('/run/host-env-vault/sockets/app-worker.sock', $path->path());
    }

    public function testForProfileTrimsRuntimeDirSlash(): void
    {
        $path = RuntimeSocketPath::forProfile('/run/host-env-vault/', 'app-web');

        $this->assertSame('/run/host-env-vault/sockets/app-web.sock', $path->path());
    }

    public function testForProfileRejectsEmptyName(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        RuntimeSocketPath::forProfile('/run/host-env-vault', '  ');
    }
}
