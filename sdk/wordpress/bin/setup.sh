#!/usr/bin/env bash
# Local WordPress + WooCommerce + Contact Form 7 with the plugin mounted, the
# plugin pointed at the local API (pnpm dev, port 19300). Idempotent.
# Admin: http://localhost:19450/wp-admin, local-only credentials admin / admin.
set -euo pipefail
cd "$(dirname "$0")/.."
w() { docker compose run --rm -T cli "$@"; }

docker compose up -d
until curl -s -o /dev/null http://localhost:19450/; do sleep 2; done

w core is-installed 2>/dev/null || w core install --url=http://localhost:19450 \
  --title="Boutique Test" --admin_user=admin --admin_password=admin \
  --admin_email=admin@example.test --skip-email
# The image ships an older core; WooCommerce needs the latest.
w core update && w core update-db
w language core install fr_FR --activate
w plugin install woocommerce contact-form-7 plugin-check --activate
w plugin activate unlimited-messaging
# Without this the plugin would call the production API.
w config set UNLIMITED_MESSAGING_API_URL http://host.docker.internal:19300 --type=constant
echo "Ready: http://localhost:19450/wp-admin/admin.php?page=unlimited-messaging"
