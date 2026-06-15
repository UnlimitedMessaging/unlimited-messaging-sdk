# unlimited-messaging

Official Python client for the [Unlimited Messaging](https://app.unlimitedmessaging.app) API — send and receive WhatsApp messages programmatically.

- **Website:** [unlimitedmessaging.app](https://unlimitedmessaging.app)
- **Docs:** [docs.unlimitedmessaging.app](https://docs.unlimitedmessaging.app)
- **API reference:** [api.unlimitedmessaging.app](https://api.unlimitedmessaging.app)
- **GitHub:** [unlimitedmessaging/unlimited-messaging-sdk](https://github.com/unlimitedmessaging/unlimited-messaging-sdk)

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
message = client.message.message_controller_send(
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
message = client.message.message_controller_send(
    recipient="+33612345678",
    text="Hello from the SDK!",
    sim_id="optional-sim-id",  # force a specific SIM
)
```

### List messages

```python
result = client.message.message_controller_find_all(
    page=1,
    limit=20,
    status="DELIVERED",   # PENDING | SENDING | SENT | DELIVERED | READ | FAILED
    search="keyword",
)

print(f"{result.total} messages")
for msg in result.data:
    print(msg.id, msg.status, msg.text)
```

### List linked SIMs

```python
sims = client.sim.sim_controller_get_linked_sims()

for sim in sims:
    print(sim.id, sim.phone_number)
```

## Async support

```python
import asyncio
from unlimited_messaging import AsyncUnlimitedMessagingApi

client = AsyncUnlimitedMessagingApi(token="your_api_token")

async def main():
    message = await client.message.message_controller_send(
        recipient="+33612345678",
        text="Hello async!",
    )
    print(message.id, message.status)

asyncio.run(main())
```

## API reference

| Method | Description |
| --- | --- |
| `message.message_controller_send` | Send a WhatsApp message |
| `message.message_controller_find_all` | List messages with pagination and filters |
| `sim.sim_controller_get_linked_sims` | List linked SIMs |

Official documentation: [docs.unlimitedmessaging.app](https://docs.unlimitedmessaging.app)
Full OpenAPI spec: [openapi.yaml](https://github.com/unlimitedmessaging/unlimited-messaging-sdk/blob/main/openapi.yaml)
