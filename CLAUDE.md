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
| `sdk/typescript/src/` | Generated TypeScript SDK source (compiled JS + .d.ts) — deleted/recreated by Fern |
| `sdk/typescript/package.json` | npm package config — survives Fern regen (outside `src/`) |
| `sdk/typescript/README.md` | npm package README — survives Fern regen (outside `src/`) |
| `sdk/python/unlimited_messaging/` | Generated Python SDK source — deleted/recreated by Fern |
| `sdk/python/pyproject.toml` | Python package config — survives Fern regen (outside `unlimited_messaging/`) |
| `sdk/python/README.md` | PyPI package README — survives Fern regen (outside `unlimited_messaging/`) |
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
# Fern only deletes sdk/typescript/src/ and sdk/python/unlimited_messaging/
# package.json, README.md, pyproject.toml all survive outside those dirs

# 2. Install locally for testing examples
make install
# This runs: pip install -e ./sdk/python
# And: cd examples/typescript && npm install

# 3. Run examples
API_TOKEN=your_token python3 examples/python/src/send_message.py
API_TOKEN=your_token npx tsx examples/typescript/src/send-message.ts
```

## Fern generator versions

| Generator | Version |
|---|---|
| fernapi/fern-typescript-node-sdk | 0.51.7 |
| fernapi/fern-python-sdk | 3.10.8 |

To upgrade: `fern generator upgrade`
To include major versions: `fern generator upgrade --include-major`

## CI workflow

Triggered on push to `main` when `openapi.yaml` changes:

1. Bumps patch version in `sdk/typescript/package.json` and `sdk/python/pyproject.toml`
2. Runs `fern generate --group sdk --local` — regenerates `sdk/typescript/src/` and `sdk/python/unlimited_messaging/`
3. Commits `sdk/` + version files with `[skip ci]` to avoid loops
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

The CI auto-bumps the patch version on every release. For minor or major bumps, edit manually before merging:

- TypeScript: `sdk/typescript/package.json` → `version`
- Python: `sdk/python/pyproject.toml` → `[project] version`
