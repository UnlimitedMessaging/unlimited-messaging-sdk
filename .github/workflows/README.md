# Release workflows

## `release.yml`: TypeScript and Python SDKs

Runs on every push to `main` and on a manual run (**Actions > Release SDKs > Run workflow**). It releases when `openapi.yaml` changed, when a hand-written file under `sdk/` changed (outside `sdk/wordpress/`), or on a manual run:

```text
openapi.yaml changed (or manual run)
  -> Fern regenerates the TypeScript and Python SDKs
_version.sh
  -> next version = max(local manifests, published registries) with the patch bumped,
     written into both manifests (one version, one tag, one GitHub release)
_publish-typescript.sh  -> npm publish --provenance
_publish-python.sh      -> PyPI
  -> commit the regenerated code and version, GitHub release, CHANGELOG.md
```

## `wordpress.yml`: WordPress plugin

The plugin (`sdk/wordpress`) has its own version (`Stable tag` in `readme.txt`) and its own channel (wordpress.org SVN). `release.yml` ignores `sdk/wordpress/**`. See the comments at the top of `wordpress.yml`.

## The n8n node

It lives in its own repository, [n8n-nodes-unlimited-messaging](https://github.com/UnlimitedMessaging/n8n-nodes-unlimited-messaging), which publishes it to npm. n8n's review requires one package per repository.

## Secrets

| Secret           | Used for                                                     |
| ---------------- | ------------------------------------------------------------ |
| `NPM_TOKEN`      | Publish `@unlimited-messaging/sdk` to npm                    |
| `PYPI_TOKEN`     | Publish `unlimited-messaging` to PyPI                        |
| `GEMINI_API_KEY` | Release notes from the OpenAPI diff (optional)               |
| `DISCORD_WEBHOOK`| Start / end notifications                                    |
| `WPORG_SVN_*`    | Deploy the WordPress plugin to wordpress.org                 |

`NPM_TOKEN` must be a Classic Automation token or a Granular token with write access to `@unlimited-messaging/sdk`. Granular tokens expire: if publishing fails with 401 or 403, regenerate it on npmjs.com and update the secret.
