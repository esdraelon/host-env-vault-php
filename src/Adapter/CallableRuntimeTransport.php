<?php

declare(strict_types=1);

namespace Amtgard\HostEnvVault\Adapter;

use Amtgard\HostEnvVault\Port\RuntimeTransport;

/**
 * Delegates wire I/O to a closure (Adapter / test double).
 */
final class CallableRuntimeTransport implements RuntimeTransport
{
    /** @var callable(string): string */
    private $handler;

    /**
     * @param callable(string): string $handler
     */
    public function __construct(callable $handler)
    {
        $this->handler = $handler;
    }

    public function exchange(string $requestFrame): string
    {
        return ($this->handler)($requestFrame);
    }
}
