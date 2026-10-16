# Milestones

## runtime socket client

- **Scope:** PHP 8.4 runtime-mode client (`RuntimeVaultClient`, frame codec, unix socket transport, optional CLI)
- **Log-tested branches:** runtime fetch denied (info), runtime fetch ok (debug)
- **CLI:** `fetch-runtime-secret` with `--socket` or `--runtime-dir` + `--profile` (no env defaults)

- **Line coverage:** 95.76% (`src/`)
- **Infection MSI:** 93% (covered MSI 95%)
