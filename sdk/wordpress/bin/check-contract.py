#!/usr/bin/env python3
"""
Checks that openapi.yaml still offers everything the WordPress plugin calls.

The plugin is hand-written PHP (like the n8n node), not generated: it only
uses `GET /account` and `POST /message`. Nothing to regenerate when the spec
changes, but a spec that renames or drops a field the plugin sends would
silently break every shop. This runs in CI on every spec update and fails
loudly instead.

    python3 sdk/wordpress/bin/check-contract.py [openapi.yaml]

Needs PyYAML (`pip install pyyaml`).
"""
from __future__ import annotations

import sys
from pathlib import Path

import yaml

ROOT = Path(__file__).resolve().parents[3]
spec_path = Path(sys.argv[1]) if len(sys.argv) > 1 else ROOT / "openapi.yaml"
spec = yaml.safe_load(spec_path.read_text(encoding="utf-8"))
errors: list[str] = []


def resolve(node: dict) -> dict:
    """Follows local $ref and merges allOf, enough for this spec."""
    while isinstance(node, dict) and "$ref" in node:
        target = spec
        for part in node["$ref"].lstrip("#/").split("/"):
            target = target[part]
        node = target
    if isinstance(node, dict) and "allOf" in node:
        merged: dict = {"type": "object", "properties": {}}
        for sub in node["allOf"]:
            merged["properties"].update(resolve(sub).get("properties", {}))
        return merged
    return node


def prop(schema: dict, path: str) -> dict | None:
    """`to.phone`, `channels[].channel`... inside an object schema."""
    node = resolve(schema)
    for part in path.split("."):
        is_list = part.endswith("[]")
        name = part[:-2] if is_list else part
        node = resolve(node).get("properties", {}).get(name)
        if node is None:
            return None
        if is_list:
            node = resolve(node).get("items")
            if node is None:
                return None
    return resolve(node)


def operation(method: str, path: str) -> dict | None:
    op = spec.get("paths", {}).get(path, {}).get(method)
    if op is None:
        errors.append(f"{method.upper()} {path} is gone")
    return op


def need(schema: dict, where: str, path: str, enum: set[str] | None = None) -> None:
    node = prop(schema, path)
    if node is None:
        errors.append(f"{where}: `{path}` is gone")
        return
    if enum:
        values = set(node.get("enum", []))
        missing = enum - values
        if missing:
            errors.append(f"{where}: `{path}` no longer accepts {sorted(missing)}")


send = operation("post", "/message")
if send:
    body = send["requestBody"]["content"]["application/json"]["schema"]
    where = "POST /message body"
    for field in ("to.phone", "to.email", "text", "channels[].accountId", "channels[].subject"):
        need(body, where, field)
    need(body, where, "channels[].channel", {"WHATSAPP", "SMS", "EMAIL"})
    need(body, where, "priority", {"transactional"})
    required = set(resolve(body).get("required", []))
    extra = required - {"to", "channels", "text"}
    if extra:
        errors.append(f"{where}: new required field(s) the plugin does not send: {sorted(extra)}")

accounts = operation("get", "/account")
if accounts:
    schema = resolve(accounts["responses"]["200"]["content"]["application/json"]["schema"])
    item = resolve(schema.get("items", {}))
    for field in ("id", "channel", "status", "name", "phone"):
        if field not in item.get("properties", {}):
            errors.append(f"GET /account item: `{field}` is gone")
    need(item, "GET /account item", "status", {"ACTIVE"})
    channels = set(prop(item, "channel").get("enum", [])) if prop(item, "channel") else set()
    if not {"EMAIL", "SMS"} <= channels or not any(c.startswith("WHATSAPP") for c in channels):
        errors.append(f"GET /account item: `channel` values changed: {sorted(channels)}")

if errors:
    print("The WordPress plugin no longer matches openapi.yaml:")
    for e in errors:
        print(f"  - {e}")
    print("Update sdk/wordpress/unlimited-messaging (includes/class-api.php, class-sender.php) before releasing.")
    sys.exit(1)
print("WordPress plugin contract OK: GET /account and POST /message offer everything it uses.")
