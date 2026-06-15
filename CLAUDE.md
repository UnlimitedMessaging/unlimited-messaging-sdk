# Unlimited Messaging SDK — Claude Context

## What this repo is

Public SDK repository for the [Unlimited Messaging](https://unlimitedmessaging.app) API.
It exposes a TypeScript (npm) and a Python (pip) client generated automatically from the OpenAPI spec.

The private NestJS API lives in a separate private repo. This repo only holds:
- the OpenAPI spec (source of truth pushed here via PR from the private repo)
- the Fern generator configuration
- the generated SDK files
- usage examples

## How it works

```
Private API repo (NestJS)
  → generates openapi.yaml
  → opens a PR here with the updated spec
  → dev merges PR
  → GitHub Action triggers: generates SDKs → commits sdk/ → publishes to npm & PyPI
```

## Key files

| File | Role |
|---|---|
| `openapi.yaml` | OpenAPI 3.0 spec, auto-synced from the private API |
| `fern/generators.yml` | Fern config: two groups — `sdk` (local output) and `production` (npm + PyPI) |
| `fern/fern.config.json` | Fern org + CLI version |
| `sdk/typescript/` | Generated TypeScript SDK (compiled JS + .d.ts) |
| `sdk/python/` | Generated Python SDK |
| `examples/` | Usage code snippets per language |
| `.github/workflows/release.yml` | CI: regenerates sdk/, commits, then publishes |

## API

- Site: https://unlimitedmessaging.app
- API base URL: https://api.unlimitedmessaging.app
- Auth: Bearer token (JWT)

## SDK packages

| Language | Package |
|---|---|
| TypeScript | `@unlimited-messaging/sdk` (npm) |
| Python | `unlimited-messaging` (PyPI) |

## Regenerating the SDKs locally

Requires Docker running.

```bash
fern generate --group sdk --local
```

Output goes to `sdk/typescript/` and `sdk/python/`.

## Fern generator versions

| Generator | Version |
|---|---|
| fernapi/fern-typescript-node-sdk | 0.51.7 |
| fernapi/fern-python-sdk | 3.10.8 |

To upgrade: `fern generator upgrade`
To include major versions: `fern generator upgrade --include-major`

## CI workflow

The workflow in `.github/workflows/release.yml` triggers on any push to `main` that touches `openapi.yaml`:
1. Runs `fern generate --group sdk --local` — regenerates `sdk/` via Docker
2. Commits the updated `sdk/` with `[skip ci]` to avoid loops
3. Runs `fern generate --group production` — publishes to npm and PyPI

Secrets required in GitHub Settings → Secrets:
- `NPM_TOKEN` — npm token with publish permission on `@unlimited-messaging/sdk`
- `PYPI_TOKEN` — PyPI token with upload permission on `unlimited-messaging`
