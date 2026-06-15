# unlimited-messaging

Official Python client for the [Unlimited Messaging](https://unlimitedmessaging.app) API — send and receive WhatsApp messages programmatically.

[![PyPI version](https://img.shields.io/pypi/v/unlimited-messaging?style=flat-square&color=3775a9)](https://pypi.org/project/unlimited-messaging/)
[![PyPI downloads](https://img.shields.io/pypi/dm/unlimited-messaging?style=flat-square&color=3775a9)](https://pypi.org/project/unlimited-messaging/)
[![PyPI total downloads](https://img.shields.io/pepy/dt/unlimited-messaging?style=flat-square&label=total%20downloads&color=3775a9)](https://pepy.tech/project/unlimited-messaging)
[![Python versions](https://img.shields.io/pypi/pyversions/unlimited-messaging?style=flat-square&color=3775a9)](https://pypi.org/project/unlimited-messaging/)
[![License](https://img.shields.io/pypi/l/unlimited-messaging?style=flat-square)](https://github.com/unlimitedmessaging/unlimited-messaging-sdk/blob/main/LICENSE)
[![CI](https://img.shields.io/github/actions/workflow/status/unlimitedmessaging/unlimited-messaging-sdk/release.yml?style=flat-square&label=CI)](https://github.com/unlimitedmessaging/unlimited-messaging-sdk/actions/workflows/release.yml)

- **Website:** [unlimitedmessaging.app](https://unlimitedmessaging.app)
- **Docs:** [docs.unlimitedmessaging.app](https://docs.unlimitedmessaging.app)
- **API reference:** [api.unlimitedmessaging.app](https://api.unlimitedmessaging.app)
- **GitHub:** [unlimitedmessaging/unlimited-messaging-sdk](https://github.com/unlimitedmessaging/unlimited-messaging-sdk)
- **PyPI:** [unlimited-messaging](https://pypi.org/project/unlimited-messaging/)

## Installation

```bash
pip install unlimited-messaging
```

## Quick start

```python
import os
from unlimited_messaging import UnlimitedMessagingApi

client = UnlimitedMessagingApi(
    token=os.environ["API_TOKEN"],
)

# Send a WhatsApp message
message = client.message.send(
    recipient="+33612345678",
    text="Hello!",
)

print(message.id, message.status)
```

## Authentication

Get your API token from the [dashboard](https://app.unlimitedmessaging.app) and pass it as the `token` argument.

```python
client = UnlimitedMessagingApi(token="your_api_token")
```

## Examples

### Send a message

```python
message = client.message.send(
    recipient="+33612345678",
    text="Hello from the SDK!",
    sim_id="optional-sim-id",  # force a specific SIM
)
```

### List messages

```python
result = client.message.find_all(
    page=1,
    limit=20,
    status="DELIVERED",   # PENDING | SENDING | SENT | DELIVERED | READ | FAILED
    search="keyword",
)

print(f"{result.total} messages")
for msg in result.data:
    print(msg.id, msg.status, msg.text)
```

### Get a single message

```python
msg = client.message.find_one("message-id")
print(msg.id, msg.status, msg.text)
```

### List linked SIMs

```python
sims = client.sim.get_linked_sims()

for sim in sims:
    print(sim.id, sim.phone_number)
```

## Async support

```python
import asyncio
from unlimited_messaging import AsyncUnlimitedMessagingApi

client = AsyncUnlimitedMessagingApi(token="your_api_token")

async def main():
    message = await client.message.send(
        recipient="+33612345678",
        text="Hello async!",
    )
    print(message.id, message.status)

asyncio.run(main())
```

## API reference

| Method                | Description                               |
| --------------------- | ----------------------------------------- |
| `message.send`        | Send a WhatsApp message                   |
| `message.find_all`    | List messages with pagination and filters |
| `message.find_one`    | Get a single message by ID                |
| `sim.get_linked_sims` | List linked SIMs                          |

Official documentation: [docs.unlimitedmessaging.app](https://docs.unlimitedmessaging.app)
Full OpenAPI spec: [openapi.yaml](https://github.com/unlimitedmessaging/unlimited-messaging-sdk/blob/main/openapi.yaml)
