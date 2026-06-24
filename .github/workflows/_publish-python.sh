#!/bin/bash
set -e

pip install build twine --quiet
cd sdk/python

LOCAL=$(python3 -c "import re; print(re.search(r'version = \"(.+?)\"', open('pyproject.toml').read()).group(1))")
REMOTE=$(python3 -c "
import urllib.request, json
try:
    with urllib.request.urlopen('https://pypi.org/pypi/unlimited-messaging/json') as r:
        print(json.loads(r.read())['info']['version'])
except:
    print('none')
")

if [ "$LOCAL" != "$REMOTE" ]; then
  echo "Publishing unlimited-messaging@$LOCAL..."
  python -m build
  twine upload dist/* --username __token__ --password "$PYPI_TOKEN" --skip-existing
  echo "published=true" >> $GITHUB_OUTPUT
  echo "version=$LOCAL" >> $GITHUB_OUTPUT
else
  echo "unlimited-messaging up to date (v$LOCAL), skipping"
fi
