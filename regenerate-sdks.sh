#!/bin/bash
set -e

echo "▶ Regenerating SDKs from openapi.yaml..."
echo "Yes" | fern generate --group sdk --local

echo "▶ Restoring TypeScript package.json..."
cp scripts/typescript-package.json sdk/typescript/package.json

echo "▶ Installing TypeScript dependencies..."
cd sdk/typescript && npm install --silent && cd ../..

echo "▶ Reinstalling Python SDK..."
pip install -q -e ./sdk/python

echo ""
echo "✓ Done. SDKs updated in sdk/typescript/ and sdk/python/unlimited_messaging/"
echo ""
echo "To commit and push:"
echo "  git add openapi.yaml sdk/ examples/"
echo "  git commit -m 'chore: regenerate SDKs'"
echo "  git push"
