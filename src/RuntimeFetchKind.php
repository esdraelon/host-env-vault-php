<?php

declare(strict_types=1);

namespace Amtgard\HostEnvVault;

/**
 * Catalog runtime list selector (Strategy key).
 */
enum RuntimeFetchKind
{
    case Env;
    case File;

    public function opcode(): RuntimeOpcode
    {
        return match ($this) {
            self::Env => RuntimeOpcode::FetchEnv,
            self::File => RuntimeOpcode::FetchFile,
        };
    }
}
