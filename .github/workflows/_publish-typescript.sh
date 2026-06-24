#!/bin/bash
set -e

cp LICENSE sdk/typescript/LICENSE
cd sdk/typescript
npm install

LOCAL=$(node -e "console.log(require('./package.json').version)")
REMOTE=$(npm view @unlimited-messaging/sdk version 2>/dev/null || echo "0.0.0")

if [ "$OPENAPI_CHANGED" = "true" ]; then
  # Bump to max(local_patch, remote_patch) + 1 to handle concurrent CI runs
  # where a parallel run already published a newer patch before us.
  LOCAL=$(node -e "
    const fs = require('fs');
    const pkg = JSON.parse(fs.readFileSync('package.json', 'utf8'));
    const [M, m, p] = pkg.version.split('.').map(Number);
    const [rM, rm, rp] = '$REMOTE'.split('.').map(Number);
    const basePatch = (M === rM && m === rm) ? Math.max(p, rp) : p;
    pkg.version = M + '.' + m + '.' + (basePatch + 1);
    fs.writeFileSync('package.json', JSON.stringify(pkg, null, 2) + '\n');
    process.stdout.write(pkg.version);
  ")
  echo "Publishing @unlimited-messaging/sdk@$LOCAL..."
  npm publish --access public --provenance
  echo "published=true" >> $GITHUB_OUTPUT
  echo "version=$LOCAL" >> $GITHUB_OUTPUT

elif [ "$LOCAL" != "$REMOTE" ]; then
  echo "Publishing @unlimited-messaging/sdk@$LOCAL..."
  npm publish --access public --provenance
  echo "published=true" >> $GITHUB_OUTPUT
  echo "version=$LOCAL" >> $GITHUB_OUTPUT

else
  echo "@unlimited-messaging/sdk up to date (v$LOCAL), skipping"
fi
