#!/usr/bin/env bash
# Checks the plugin with Plugin Check, refreshes translations and builds the
# zip uploaded to wordpress.org (first submission) or copied to SVN trunk.
set -euo pipefail
cd "$(dirname "$0")/.."
w() { docker compose run --rm -T cli "$@"; }
P=wp-content/plugins/unlimited-messaging

w plugin check unlimited-messaging
w i18n make-pot $P $P/languages/unlimited-messaging.pot --exclude=languages
w i18n make-mo $P/languages
w i18n make-php $P/languages

mkdir -p dist
rm -f dist/unlimited-messaging.zip
zip -rq dist/unlimited-messaging.zip unlimited-messaging -x '*.DS_Store'
echo "dist/unlimited-messaging.zip"
