.PHONY: generate install install-py install-ts install-n8n build-n8n

# Regenerate SDKs from openapi.yaml (requires Docker)
generate:
	echo "Yes" | fern generate --group sdk --local
	cp scripts/typescript-package.json sdk/typescript/package.json

# Install all SDKs locally for testing
install: install-py install-ts install-n8n

install-py:
	pip install -e ./sdk/python

install-ts:
	cd examples/typescript && npm install

install-n8n:
	cd sdk/n8n && npm install

build-n8n: install-n8n
	cd sdk/n8n && npm run build
