.PHONY: generate install install-py install-ts

# Regenerate SDKs from openapi.yaml (requires Docker)
generate:
	echo "Yes" | fern generate --group sdk --local
	cp scripts/typescript-package.json sdk/typescript/package.json

# Install both SDKs locally for testing examples
install: install-py install-ts

install-py:
	pip install -e ./sdk/python

install-ts:
	cd examples/typescript && npm install
