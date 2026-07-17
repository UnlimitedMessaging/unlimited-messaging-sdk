#!/bin/bash
set -euo pipefail

# Publish the TypeScript SDK at whatever version _version.sh already wrote into
# package.json. Version selection lives there, once, for all three packages.
#
# There is no "already published, skip" guard: _version.sh always yields a
# version strictly above every registry, so a collision cannot happen. If one
# ever did, npm failing loudly is the right outcome.

PACKAGE="@unlimited-messaging/sdk"

cp LICENSE sdk/typescript/LICENSE
cd sdk/typescript
npm install

VERSION=$(node -pe "require('./package.json').version")

echo "Publishing $PACKAGE@$VERSION..."
npm publish --access public --provenance

echo "published=true" >> "$GITHUB_OUTPUT"
echo "version=$VERSION" >> "$GITHUB_OUTPUT"
