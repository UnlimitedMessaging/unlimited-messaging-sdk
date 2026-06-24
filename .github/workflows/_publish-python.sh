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

if [ "$OPENAPI_CHANGED" = "true" ]; then
  LOCAL=$(python3 << 'EOF'
import re, sys
with open('pyproject.toml', 'r') as f:
    content = f.read()
def bump(m):
    return f'version = "{m.group(1)}.{m.group(2)}.{int(m.group(3))+1}"'
content = re.sub(r'version = "(\d+)\.(\d+)\.(\d+)"', bump, content, count=1)
with open('pyproject.toml', 'w') as f:
    f.write(content)
v = re.search(r'version = "(.+?)"', content).group(1)
print(v, file=sys.stderr)
sys.stdout.write(v)
EOF
)
  python -m build
  twine upload dist/* --username __token__ --password "$PYPI_TOKEN" --skip-existing
  echo "published=true" >> $GITHUB_OUTPUT
  echo "version=$LOCAL" >> $GITHUB_OUTPUT

elif [ "$LOCAL" != "$REMOTE" ]; then
  echo "Local v$LOCAL != published v$REMOTE, publishing..."
  python -m build
  twine upload dist/* --username __token__ --password "$PYPI_TOKEN" --skip-existing
  echo "published=true" >> $GITHUB_OUTPUT
  echo "version=$LOCAL" >> $GITHUB_OUTPUT

else
  echo "Python SDK up to date (v$LOCAL), skipping"
fi
