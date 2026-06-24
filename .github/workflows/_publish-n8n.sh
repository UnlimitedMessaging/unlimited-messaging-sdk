#!/bin/bash
set -e

cp LICENSE sdk/n8n/LICENSE
cd sdk/n8n
npm install
npm run build

LOCAL=$(node -e "console.log(require('./package.json').version)")
REMOTE=$(npm view n8n-nodes-unlimited-messaging version 2>/dev/null || echo "none")

if [ "$N8N_CHANGED" = "true" ] && [ "$LOCAL" = "$REMOTE" ]; then
  # n8n files changed but version not bumped — auto-bump patch
  LOCAL=$(node -e "
    const fs = require('fs');
    const pkg = JSON.parse(fs.readFileSync('package.json', 'utf8'));
    const [M,m,p] = pkg.version.split('.').map(Number);
    pkg.version = \`\${M}.\${m}.\${p+1}\`;
    fs.writeFileSync('package.json', JSON.stringify(pkg, null, 2) + '\n');
    console.error('n8n version bumped to:', pkg.version);
    process.stdout.write(pkg.version);
  ")
  echo "n8n files changed, bumped to v$LOCAL, publishing..."
  npm publish --access public --provenance
  echo "published=true" >> $GITHUB_OUTPUT
  echo "version=$LOCAL" >> $GITHUB_OUTPUT

elif [ "$LOCAL" != "$REMOTE" ]; then
  echo "Local v$LOCAL != published v$REMOTE, publishing..."
  npm publish --access public --provenance
  echo "published=true" >> $GITHUB_OUTPUT
  echo "version=$LOCAL" >> $GITHUB_OUTPUT

else
  echo "n8n node up to date (v$LOCAL), skipping"
fi
