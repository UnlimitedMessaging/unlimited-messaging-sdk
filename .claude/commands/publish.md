# Publish a new SDK version

Publishing is **version-driven**: the CI compares the version in the source file against the currently published version on npm/PyPI. If they differ, it publishes. If they match, it skips.

**Nothing is auto-bumped by CI.** You control when a release happens by editing the version.

---

## To publish a new version

Edit the version in the relevant file, then push to `main`. CI handles the rest.

| Package | File to edit | Field |
|---|---|---|
| TypeScript SDK (`@unlimited-messaging/sdk`) | `sdk/typescript/package.json` | `"version"` |
| Python SDK (`unlimited-messaging`) | `sdk/python/pyproject.toml` | `version` under `[project]` |
| n8n node (`n8n-nodes-unlimited-messaging`) | `sdk/n8n/package.json` | `"version"` |

Use [semver](https://semver.org): `MAJOR.MINOR.PATCH`
- **patch** (0.1.2 → 0.1.3): bug fix, no API change
- **minor** (0.1.x → 0.2.0): new endpoint or feature, backwards-compatible
- **major** (0.x.x → 1.0.0): breaking change

You can bump one, two, or all three in the same commit — CI publishes whichever versions changed.

---

## What CI does on push to `main`

1. Reads the local version from each package file
2. Reads the currently published version from npm / PyPI
3. If `local != remote` → builds and publishes
4. If `local == remote` → skips

For the TypeScript and Python SDKs, if `openapi.yaml` also changed in the same push, Fern regenerates the SDK source first, then publishes.

---

## Checking current versions

```bash
# npm packages
npm view @unlimited-messaging/sdk version
npm view n8n-nodes-unlimited-messaging version

# PyPI
pip index versions unlimited-messaging 2>/dev/null | head -1
```
