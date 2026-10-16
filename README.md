# host-env-vault-php

Composer library for [host-env-vault](https://github.com/esdraelon/host-env-vault) **runtime** profiles: fetch env and file secrets over the profile unix socket while `serve` is running.

Inject-mode secrets (`render` + `secrets.env`) stay plain file reads; this package covers the framed socket protocol only.

## Requirements

- PHP 8.4+
- `ext-sockets`

## Install

```bash
composer require amtgard/host-env-vault
```

## Usage

Point at the socket path (`{runtime_dir}/sockets/{profile}.sock`) or pass `runtime_dir` + profile name:

```php
use Amtgard\HostEnvVault\RuntimeVaultClient;

$client = RuntimeVaultClient::builder()
    ->build()
    ->forRuntimeProfile('/run/host-env-vault', 'app-worker');

$apiSecret = $client->fetchEnv('API_SECRET');
$pem = $client->fetchFile('signing.pem');
```

Custom socket path:

```php
$client = RuntimeVaultClient::builder()
    ->build()
    ->socketPath('/var/run/host-env-vault.sock');
```

Optional PSR-3 logger (debug on success, info on deny):

```php
$client->logger($logger);
```

Complete each fetch promptly; the agent holds decrypted material under a short lease (see host-env-vault docs).

## Wire format

Matches host-env-vault `serve`:

| Direction | Bytes |
| --- | --- |
| Request | `opcode (u8)` + `length (u32 LE)` + `entry name (UTF-8)` |
| Response | `status (u8, 0=ok)` + `length (u32 LE)` + `secret bytes` |

Opcodes: `1` env, `2` file.

## Development

```bash
composer install
composer test
```

Coverage and mutation gates follow the ORK4 development rules (`docs/reference/milestones.md`).
