#!/bin/bash
set -e

cp LICENSE sdk/typescript/LICENSE
cd sdk/typescript
npm install

LOCAL=$(node -e "console.log(require('./package.json').version)")
REMOTE=$(npm view @unlimited-messaging/sdk version 2>/dev/null || echo "none")

if [ "$OPENAPI_CHANGED" = "true" ]; then
  node -e "
    const fs = require('fs');
    const pkg = JSON.parse(fs.readFileSync('package.json', 'utf8'));
    const [M,m,p] = pkg.version.split('.').map(Number);
    pkg.version = \`\${M}.\${m}.\${p+1}\`;
    fs.writeFileSync('package.json', JSON.stringify(pkg, null, 2) + '\n');
    console.log('TypeScript version:', pkg.version);
  "
  npm publish --access public --provenance
  echo "published=true" >> $GITHUB_OUTPUT

elif [ "$LOCAL" != "$REMOTE" ]; then
  echo "Local v$LOCAL != published v$REMOTE, publishing..."
  npm publish --access public --provenance
  echo "published=true" >> $GITHUB_OUTPUT

else
  echo "TypeScript SDK up to date (v$LOCAL), skipping"
fi
