#!/bin/bash
set -euo pipefail

# Publish the n8n community node at whatever version _version.sh already wrote
# into package.json. Version selection lives there, once, for all three packages.
#
# There is no "already published, skip" guard: _version.sh always yields a
# version strictly above every registry, so a collision cannot happen. If one
# ever did, npm failing loudly is the right outcome.

PACKAGE="n8n-nodes-unlimited-messaging"

cp LICENSE sdk/n8n/LICENSE
cd sdk/n8n
npm install
npm run build

VERSION=$(node -pe "require('./package.json').version")

echo "Publishing $PACKAGE@$VERSION..."
npm publish --access public --provenance

echo "published=true" >> "$GITHUB_OUTPUT"
echo "version=$VERSION" >> "$GITHUB_OUTPUT"