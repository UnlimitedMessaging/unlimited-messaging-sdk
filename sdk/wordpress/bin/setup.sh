#!/usr/bin/env bash
# A small local WordPress shop with the plugin mounted, ready to try:
# WooCommerce (France, EUR, cash on delivery, one product, store open),
# Contact Form 7 on a Contact page, French admin. Idempotent.
#
#   bin/setup.sh          plugin talks to the local API (pnpm dev, port 19300),
#                         plus Plugin Check for bin/build.sh
#   bin/setup.sh --prod   plugin talks to https://api.unlimitedmessaging.app,
#                         to try it with a real account and real channels
#
# Admin: http://localhost:19450/wp-admin, user `admin`. The password is
# generated on first run and kept in .env.test.local (gitignored).
set -euo pipefail
cd "$(dirname "$0")/.."
w() { docker compose run --rm -T cli "$@"; }

PROD=false
[ "${1:-}" = "--prod" ] && PROD=true

if [ ! -f .env.test.local ] || ! grep -q '^WP_ADMIN_PASSWORD=' .env.test.local; then
  echo "WP_ADMIN_PASSWORD=$(openssl rand -hex 10)" >> .env.test.local
fi
WP_ADMIN_PASSWORD=$(sed -n 's/^WP_ADMIN_PASSWORD=//p' .env.test.local)

docker compose up -d
until curl -s -o /dev/null http://localhost:19450/; do sleep 2; done

w core is-installed 2>/dev/null || w core install --url=http://localhost:19450 \
  --title="Boutique Test" --admin_user=admin --admin_password="$WP_ADMIN_PASSWORD" \
  --admin_email=admin@example.test --skip-email
# The image ships an older core; WooCommerce needs the latest.
w core update && w core update-db
w language core install fr_FR --activate
w rewrite structure '/%postname%/' --hard
w plugin install woocommerce contact-form-7 --activate
w plugin activate unlimited-messaging

if [ "$PROD" = true ]; then
  w config delete UNLIMITED_MESSAGING_API_URL 2>/dev/null || true
else
  w plugin install plugin-check --activate
  w config set UNLIMITED_MESSAGING_API_URL http://host.docker.internal:19300 --type=constant
fi

# Shop basics: France, euro, open to visitors, cash on delivery so a test
# order goes through without a payment provider.
w option update woocommerce_default_country 'FR'
w option update woocommerce_currency 'EUR'
w option update woocommerce_coming_soon 'no'
w option update woocommerce_onboarding_profile '{"skipped":true}' --format=json
w option update woocommerce_cod_settings '{"enabled":"yes","title":"Paiement à la livraison","description":"Payez en espèces à la livraison.","instructions":""}' --format=json
w wc tool run install_pages --user=admin >/dev/null
if [ -z "$(w post list --post_type=product --format=ids)" ]; then
  w wc product create --name="T-shirt test" --type=simple --regular_price=19 --status=publish --user=admin >/dev/null
fi

# A Contact page with Contact Form 7's default form, to try form alerts.
FORM_ID=$(w post list --post_type=wpcf7_contact_form --field=ID --posts_per_page=1)
if [ -n "$FORM_ID" ] && [ -z "$(w post list --post_type=page --name=contact --format=ids)" ]; then
  w post create --post_type=page --post_status=publish --post_title=Contact --post_name=contact \
    --post_content="[contact-form-7 id=\"$FORM_ID\"]" >/dev/null
fi

echo
echo "Shop:     $(w eval 'echo get_permalink( wc_get_page_id( "shop" ) );' 2>/dev/null)"
echo "Contact:  http://localhost:19450/contact/"
echo "Plugin:   http://localhost:19450/wp-admin/admin.php?page=unlimited-messaging"
echo "Admin:    admin, password in $(pwd)/.env.test.local (WP_ADMIN_PASSWORD)"
echo "API:      $([ "$PROD" = true ] && echo 'production (https://api.unlimitedmessaging.app)' || echo 'local (http://localhost:19300)')"
