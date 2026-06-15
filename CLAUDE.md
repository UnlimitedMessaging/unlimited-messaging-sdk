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
  → GitHub Action triggers:
      1. fern generate --group sdk --local  → regenerates sdk/, commits
      2. npm publish                         → publishes TypeScript to npm
      3. twine upload                        → publishes Python to PyPI
```

## Key files

| File | Role |
|---|---|
| `openapi.yaml` | OpenAPI 3.0 spec, auto-synced from the private API |
| `fern/generators.yml` | Fern config: `sdk` group (local output to sdk/) and `production` group (unused — publishing is done directly) |
| `fern/fern.config.json` | Fern org + CLI version |
| `sdk/typescript/` | Generated TypeScript SDK (compiled JS + .d.ts) |
| `sdk/typescript/package.json` | npm package config (restored from `scripts/typescript-package.json` after each Fern regen) |
| `sdk/python/unlimited_messaging/` | Generated Python SDK source |
| `sdk/python/pyproject.toml` | Python package config (pip install -e ./sdk/python) — survives Fern regen |
| `scripts/typescript-package.json` | Template for sdk/typescript/package.json (Fern deletes the dir on regen) |
| `examples/typescript/` | TypeScript usage examples (tsx runner) |
| `examples/python/` | Python usage examples |
| `Makefile` | Local dev commands: `make generate`, `make install` |
| `.github/workflows/release.yml` | CI: regenerates sdk/, commits, publishes to npm + PyPI directly |

## API

- Site: https://unlimitedmessaging.app
- API base URL: https://api.unlimitedmessaging.app
- Auth: Bearer token (JWT) — no need to pass base_url, it defaults to the production API

## SDK packages

| Language | Package | Import |
|---|---|---|
| TypeScript | `@unlimited-messaging/sdk` (npm) | `UnlimitedMessagingApiClient` |
| Python | `unlimited-messaging` (PyPI) | `from unlimited_messaging import UnlimitedMessagingApi` |

## Local development

Requires Docker (for Fern).

```bash
# 1. Regenerate SDKs from openapi.yaml
make generate
# This runs: echo "Yes" | fern generate --group sdk --local
# Then copies scripts/typescript-package.json → sdk/typescript/package.json

# 2. Install locally for testing examples
make install
# This runs: pip install -e ./sdk/python
# And: cd examples/typescript && npm install

# 3. Run examples
API_TOKEN=your_token python3 examples/python/send_message.py
API_TOKEN=your_token npx tsx examples/typescript/send-message.ts
```

## Why `make generate` is needed after Fern regen

Fern does `rm -rf sdk/typescript/` and `rm -rf sdk/python/unlimited_messaging/` on each run.
- `sdk/typescript/package.json` is deleted → restored by `make generate` from `scripts/typescript-package.json`
- `sdk/python/pyproject.toml` survives (it's in `sdk/python/`, not the deleted `sdk/python/unlimited_messaging/`)

## Fern generator versions

| Generator | Version |
|---|---|
| fernapi/fern-typescript-node-sdk | 0.51.7 |
| fernapi/fern-python-sdk | 3.10.8 |

To upgrade: `fern generator upgrade`
To include major versions: `fern generator upgrade --include-major`

## CI workflow

Triggered on push to `main` when `openapi.yaml` changes:
1. Runs `fern generate --group sdk --local` — regenerates `sdk/` via Docker
2. Restores `sdk/typescript/package.json` from `scripts/typescript-package.json`
3. Commits `sdk/` with `[skip ci]` to avoid loops
4. Publishes TypeScript: `npm publish --access public` from `sdk/typescript/`
5. Publishes Python: `python -m build && twine upload` from `sdk/python/`

No Fern cloud auth needed — publishing is done directly with npm/twine.

Secrets required in GitHub Settings → Secrets:

- `NPM_TOKEN` — granular access token with read+write on `@unlimited-messaging/sdk` and "bypass 2FA" enabled. **Expires periodically** — if CI publish fails with 403, regenerate it on npmjs.com → Account Settings → Access Tokens, then update the secret in GitHub Settings → Secrets.
- `PYPI_TOKEN` — PyPI token with upload permission on `unlimited-messaging`

## Package READMEs

Each package has its own README displayed on npm and PyPI:

- `sdk/typescript/README.md` → shown on [npmjs.com/@unlimited-messaging/sdk](https://www.npmjs.com/package/@unlimited-messaging/sdk)
- `sdk/python/README.md` → shown on [pypi.org/project/unlimited-messaging](https://pypi.org/project/unlimited-messaging/)

**Always keep these files up to date** when adding new endpoints, changing method names, or updating examples. These are the public-facing docs for SDK users — the root `README.md` is for the GitHub repo only.

## SDK versioning

Versions are set manually:
- TypeScript: `scripts/typescript-package.json` → `version`
- Python: `sdk/python/pyproject.toml` → `[project] version`

Bump both before merging a PR that should trigger a new package release.
