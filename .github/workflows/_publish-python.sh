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
    print('0.0.0')
")

if [ "$OPENAPI_CHANGED" = "true" ]; then
  # Bump to max(local_patch, remote_patch) + 1 to handle concurrent CI runs
  # where a parallel run already published a newer patch before us.
  LOCAL=$(python3 << EOF
import re, sys

local = "$LOCAL"
remote = "$REMOTE"

def parse(v):
    return list(map(int, v.split('.')))

lM, lm, lp = parse(local)
rM, rm, rp = parse(remote)

base_patch = max(lp, rp) if (lM == rM and lm == rm) else lp
new_version = f"{lM}.{lm}.{base_patch + 1}"

content = open('pyproject.toml').read()
content = re.sub(r'version = "\d+\.\d+\.\d+"', f'version = "{new_version}"', content, count=1)
open('pyproject.toml', 'w').write(content)

sys.stdout.write(new_version)
EOF
)
  echo "Publishing unlimited-messaging@$LOCAL..."
  python -m build
  twine upload dist/* --username __token__ --password "$PYPI_TOKEN" --skip-existing
  echo "published=true" >> $GITHUB_OUTPUT
  echo "version=$LOCAL" >> $GITHUB_OUTPUT

elif [ "$LOCAL" != "$REMOTE" ]; then
  echo "Publishing unlimited-messaging@$LOCAL..."
  python -m build
  twine upload dist/* --username __token__ --password "$PYPI_TOKEN" --skip-existing
  echo "published=true" >> $GITHUB_OUTPUT
  echo "version=$LOCAL" >> $GITHUB_OUTPUT

else
  echo "unlimited-messaging up to date (v$LOCAL), skipping"
fi
