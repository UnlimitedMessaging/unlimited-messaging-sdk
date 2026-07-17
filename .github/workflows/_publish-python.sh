#!/bin/bash
set -euo pipefail

# Publish the Python SDK at whatever version _version.sh already wrote into
# pyproject.toml. Version selection lives there, once, for all three packages.
#
# There is no "already published, skip" guard: _version.sh always yields a
# version strictly above every registry, so a collision cannot happen. twine's
# --skip-existing stays as a last resort rather than a strategy.

PACKAGE="unlimited-messaging"

pip install build twine --quiet
cd sdk/python

VERSION=$(python3 -c "import re; print(re.search(r'version = \"(.+?)\"', open('pyproject.toml').read()).group(1))")

echo "Publishing $PACKAGE@$VERSION..."
# Stale artifacts from an earlier version would otherwise be uploaded too.
rm -rf dist
python -m build
twine upload dist/* --username __token__ --password "$PYPI_TOKEN" --skip-existing

echo "published=true" >> "$GITHUB_OUTPUT"
echo "version=$VERSION" >> "$GITHUB_OUTPUT"