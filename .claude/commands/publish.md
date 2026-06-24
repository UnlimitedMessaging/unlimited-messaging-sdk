# Publish a new SDK version

All three packages follow the **same logic**:

1. If `openapi.yaml` changed → CI auto-bumps the patch version and publishes all 3
2. If local version ≠ remote → CI publishes (manual release for any or all packages)
3. If local version == remote and openapi didn't change → skipped

---

## To publish a new version manually

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

You can bump one, two, or all three in the same commit.

---

## What CI does on push to `main`

For each of the 3 packages independently:

- If `openapi.yaml` changed → auto-bump to `max(local_patch, remote_patch) + 1` and publish. The `max` prevents conflicts when two CI runs fire simultaneously (e.g. two openapi PRs merged back-to-back — the second run reads the patch already published by the first and increments past it).
- If local version ≠ remote (manual bump) → publish as-is.
- If local version == remote and openapi unchanged → skip.

---

## Checking current versions

```bash
npm view @unlimited-messaging/sdk version
npm view n8n-nodes-unlimited-messaging version
pip index versions unlimited-messaging 2>/dev/null | head -1
```
