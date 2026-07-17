#!/bin/bash
set -euo pipefail

# Compute the next version, shared by all three packages, and write it into
# every manifest.
#
# Why one version for three packages: they are generated from the same spec and
# released together, so independent versions only ever produced ambiguity. The
# GitHub release tag was derived from the TypeScript SDK alone, so the first
# publish that did not move it - an n8n-only change - tried to reuse the
# existing v0.1.4 tag and failed the pipeline. The versions had already drifted
# apart in practice (TS 0.1.4, Python 0.1.4, n8n 0.1.6): that was not a design,
# it was a side effect.
#
# The cost is republishing packages whose code did not change. At three packages
# and this release cadence that is noise, and it buys a model where "0.1.7"
# unambiguously means all three packages at 0.1.7, one tag, one release.
#
# The next version is max(every local manifest, every published registry) with
# the patch bumped. Taking the max of both sides means a manifest that drifted
# behind its registry - or a concurrent run that published first - can never
# make us pick a version that already exists.
#
# Outputs `version=X.Y.Z` to $GITHUB_OUTPUT and prints it on stdout.

npm_latest() {
  npm view "$1" version 2>/dev/null || echo "0.0.0"
}

pypi_latest() {
  python3 - "$1" <<'PY'
import json, sys, urllib.request
try:
    with urllib.request.urlopen(f"https://pypi.org/pypi/{sys.argv[1]}/json", timeout=15) as r:
        print(json.loads(r.read())["info"]["version"])
except Exception:
    # A registry we cannot reach must not silently lower the computed version;
    # 0.0.0 simply lets the other sources win the max.
    print("0.0.0")
PY
}

TS_LOCAL=$(node -pe "require('./sdk/typescript/package.json').version")
N8N_LOCAL=$(node -pe "require('./sdk/n8n/package.json').version")
PY_LOCAL=$(python3 -c "import re; print(re.search(r'version = \"(.+?)\"', open('sdk/python/pyproject.toml').read()).group(1))")

TS_REMOTE=$(npm_latest "@unlimited-messaging/sdk")
N8N_REMOTE=$(npm_latest "n8n-nodes-unlimited-messaging")
PY_REMOTE=$(pypi_latest "unlimited-messaging")

echo "Local:  ts=$TS_LOCAL python=$PY_LOCAL n8n=$N8N_LOCAL"
echo "Remote: ts=$TS_REMOTE python=$PY_REMOTE n8n=$N8N_REMOTE"

NEXT=$(node -e '
const parse = (v) => {
  const parts = String(v).split(".").map(Number);
  if (parts.length !== 3 || parts.some(Number.isNaN)) return [0, 0, 0];
  return parts;
};
const highest = process.argv.slice(1).map(parse).reduce((a, b) => {
  for (let i = 0; i < 3; i++) {
    if (b[i] > a[i]) return b;
    if (b[i] < a[i]) return a;
  }
  return a;
}, [0, 0, 0]);
console.log(`${highest[0]}.${highest[1]}.${highest[2] + 1}`);
' "$TS_LOCAL" "$N8N_LOCAL" "$PY_LOCAL" "$TS_REMOTE" "$N8N_REMOTE" "$PY_REMOTE")

echo "Next:   $NEXT"

# Write it everywhere, so the repo and the registries never disagree.
node -e '
const fs = require("fs");
const version = process.argv[1];
for (const path of ["sdk/typescript/package.json", "sdk/n8n/package.json"]) {
  const pkg = JSON.parse(fs.readFileSync(path, "utf8"));
  pkg.version = version;
  fs.writeFileSync(path, JSON.stringify(pkg, null, 2) + "\n");
}
' "$NEXT"

python3 - "$NEXT" <<'PY'
import re, sys
version = sys.argv[1]
path = "sdk/python/pyproject.toml"
content = open(path).read()
content, count = re.subn(r'version = "\d+\.\d+\.\d+"', f'version = "{version}"', content, count=1)
if count != 1:
    raise SystemExit(f"Could not rewrite the version in {path} - the file's shape changed.")
open(path, "w").write(content)
PY

if [ -n "${GITHUB_OUTPUT:-}" ]; then
  echo "version=$NEXT" >> "$GITHUB_OUTPUT"
fi

echo "$NEXT"