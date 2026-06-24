#!/bin/bash
set -e

cp LICENSE sdk/n8n/LICENSE
cd sdk/n8n
npm install
npm run build

LOCAL=$(node -e "console.log(require('./package.json').version)")
REMOTE=$(npm view n8n-nodes-unlimited-messaging version 2>/dev/null || echo "none")

if [ "$LOCAL" != "$REMOTE" ]; then
  echo "Publishing n8n-nodes-unlimited-messaging@$LOCAL..."
  npm publish --access public --provenance
  echo "published=true" >> $GITHUB_OUTPUT
  echo "version=$LOCAL" >> $GITHUB_OUTPUT
else
  echo "n8n-nodes-unlimited-messaging up to date (v$LOCAL), skipping"
fi
