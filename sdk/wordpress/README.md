# WordPress plugin

`unlimited-messaging/` is the plugin published on wordpress.org as
**Unlimited Messaging: Order Notifications & Form Alerts** (slug
`unlimited-messaging`). It sends WooCommerce order updates and form alerts
(Contact Form 7, WPForms, Gravity Forms, Elementor Pro, Fluent Forms, Ninja
Forms) on WhatsApp, SMS and email through `POST /message`, with the shop's API
key.

Like the n8n node next to it, it is hand-written and a thin client of the
public API: no logic lives here that the API should own. It only calls
`GET /account` and `POST /message`, so nothing is generated from the spec;
instead `bin/check-contract.py` checks on every spec sync that `openapi.yaml`
still offers every field the plugin sends or reads, and fails CI otherwise.

## Layout

| File | Role |
| ---- | ---- |
| `unlimited-messaging.php` | Plugin header, constants, HPOS / blocks compatibility |
| `includes/class-settings.php` | The one option array, defaults, event list, default texts |
| `includes/class-api.php` | `GET /account`, `POST /message`, sends `X-UM-Client: wordpress` |
| `includes/class-sender.php` | Payload (`to` + `channels`, `priority: transactional`), Action Scheduler queue, hourly cap |
| `includes/class-woocommerce.php` | Status changes and customer notes, once per order and event |
| `includes/class-forms.php` | Form plugin hooks, fields to "Label: value" lines |
| `includes/class-templates.php` | `{placeholders}`, phone numbers to E.164 |
| `includes/class-admin.php` | Settings screen: Connection, WooCommerce, Forms, Activity |
| `readme.txt` | The wordpress.org listing |
| `languages/` | `.pot` + French (`.po`, `.mo`, `.l10n.php`) |

## Analytics

The API recognises the plugin by `X-UM-Client: wordpress`: `api_request.client`
is `wordpress`, product events carry `surface: wordpress`, and the first call
emits `first_wordpress_call`. Links from the plugin and its readme carry
`utm_source=wordpress`, and a wordpress.org referrer counts as the `wordpress`
acquisition channel. See `docs/analytics.md` in the private repo.

## Run it locally

```bash
pnpm dev                       # in the private repo: API on 19300
sdk/wordpress/bin/setup.sh     # here: WordPress on 19450, plugin mounted
```

Then paste an API key created on the local dashboard. `setup.sh` points the
plugin at the local API (`UNLIMITED_MESSAGING_API_URL`); without that constant
it calls production.

Useful commands (from this folder):

```bash
docker compose run --rm -T cli action-scheduler run --hooks=unlimited_messaging_send
docker compose run --rm -T cli plugin check unlimited-messaging
python3 bin/check-contract.py ../../openapi.yaml
npm install && node bin/screenshots.mjs   # listing screenshots
```

## Release

Released by `.github/workflows/wordpress.yml`, separately from the SDKs: the
plugin has its own version, and a change under `sdk/wordpress` does not
trigger `release.yml`.

1. Bump `Version` in `unlimited-messaging.php`, `UNLIMITED_MESSAGING_VERSION`,
   `Stable tag` and the changelog in `readme.txt` (CI refuses a mismatch).
2. Merge to `main`. The workflow runs the contract check and Plugin Check, then
   deploys to wordpress.org SVN (trunk + `tags/<version>`) when that version
   is not there yet, with the listing images from `.wordpress-org/`.
3. First release only: wordpress.org reviews new plugins by hand. Build the
   zip with `bin/build.sh`, upload it at
   https://wordpress.org/plugins/developers/add/, and once approved add the
   `WPORG_SVN_USERNAME` / `WPORG_SVN_PASSWORD` secrets to this repo.

`.wordpress-org/` holds the listing images: banners and icons come from the
private repo's press kit generator (`apps/landing/assets/listing/generate.py`),
screenshots from `bin/screenshots.mjs`.

wordpress.org rule to keep in mind: "WhatsApp" may not appear in the plugin
name (Plugin Check flags it). It is fine in the description, readme and tags.
