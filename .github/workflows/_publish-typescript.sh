#!/bin/bash
set -e

cp LICENSE sdk/typescript/LICENSE
cd sdk/typescript
npm install

LOCAL=$(node -e "console.log(require('./package.json').version)")
REMOTE=$(npm view @unlimited-messaging/sdk version 2>/dev/null || echo "none")

if [ "$LOCAL" != "$REMOTE" ]; then
  echo "Publishing @unlimited-messaging/sdk@$LOCAL..."
  npm publish --access public --provenance
  echo "published=true" >> $GITHUB_OUTPUT
  echo "version=$LOCAL" >> $GITHUB_OUTPUT
else
  echo "@unlimited-messaging/sdk up to date (v$LOCAL), skipping"
fi
