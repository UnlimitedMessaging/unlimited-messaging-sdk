# Unlimited Messaging SDK

Official TypeScript and Python clients for the [Unlimited Messaging](https://unlimitedmessaging.app) API — send and receive WhatsApp messages programmatically.

## Installation

**TypeScript / Node.js**
```bash
npm install @unlimited-messaging/sdk
```

**Python**
```bash
pip install unlimited-messaging
```

## Quick start

### TypeScript

```typescript
import { UnlimitedMessagingApiClient } from "@unlimited-messaging/sdk";

const client = new UnlimitedMessagingApiClient({
  environment: "https://api.unlimitedmessaging.app",
  token: process.env.API_TOKEN,
});

// Send a message
const message = await client.message.messageControllerSend({
  recipient: "+33612345678",
  text: "Hello!",
});

console.log(message.body.id, message.body.status);

// List messages
const { body } = await client.message.messageControllerFindAll({
  page: 1,
  limit: 20,
});

console.log(`${body.total} messages`);

// List linked SIMs
const sims = await client.sim.simControllerGetLinkedSims();
```

### Python

```python
import os
from unlimited_messaging import UnlimitedMessagingApi

client = UnlimitedMessagingApi(
    base_url="https://api.unlimitedmessaging.app",
    token=os.environ["API_TOKEN"],
)

# Send a message
message = client.message.message_controller_send(
    recipient="+33612345678",
    text="Hello!",
)

print(message.id, message.status)

# List messages
result = client.message.message_controller_find_all(page=1, limit=20)
print(f"{result.total} messages")

# List linked SIMs
sims = client.sim.sim_controller_get_linked_sims()
```

## Authentication

All endpoints require a Bearer token. Pass it as the `token` option when initialising the client. You can generate a token from your [dashboard](https://unlimitedmessaging.app).

## API reference

The full API spec is in [`openapi.yaml`](./openapi.yaml). More examples are available in [`examples/`](./examples/).

### Messages

| Method | Description |
|---|---|
| `message.messageControllerSend` | Send a WhatsApp message |
| `message.messageControllerFindAll` | List messages with pagination and filters |

**Send options**

| Field | Type | Required | Description |
|---|---|---|---|
| `recipient` | string | yes | Phone number (E.164 format) |
| `text` | string | yes | Message content (max 1600 chars) |
| `simId` | string | no | Force a specific SIM |

**List filters**

| Field | Type | Description |
|---|---|---|
| `page` | number | Page number (default: 1) |
| `limit` | number | Results per page, max 100 (default: 20) |
| `status` | string | Filter by status: `PENDING` `SENDING` `SENT` `DELIVERED` `READ` `FAILED` |
| `simId` | string | Filter by SIM |
| `search` | string | Search in content |

### SIMs

| Method | Description |
|---|---|
| `sim.simControllerGetLinkedSims` | Get the list of linked SIMs |

## Async support (Python)

```python
import asyncio
from unlimited_messaging import AsyncUnlimitedMessagingApi

client = AsyncUnlimitedMessagingApi(
    base_url="https://api.unlimitedmessaging.app",
    token="...",
)

async def main():
    message = await client.message.message_controller_send(
        recipient="+33612345678",
        text="Hello async!",
    )

asyncio.run(main())
```

## Links

- Website: [unlimitedmessaging.app](https://unlimitedmessaging.app)
- API: [api.unlimitedmessaging.app](https://api.unlimitedmessaging.app)
