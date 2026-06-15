# @unlimited-messaging/sdk

Official TypeScript / Node.js client for the [Unlimited Messaging](https://unlimitedmessaging.app) API — send and receive WhatsApp messages programmatically.

- **Website:** [unlimitedmessaging.app](https://unlimitedmessaging.app)
- **Docs:** [docs.unlimitedmessaging.app](https://docs.unlimitedmessaging.app)
- **API reference:** [api.unlimitedmessaging.app](https://api.unlimitedmessaging.app)
- **GitHub:** [unlimitedmessaging/unlimited-messaging-sdk](https://github.com/unlimitedmessaging/unlimited-messaging-sdk)

## Installation

```bash
npm install @unlimited-messaging/sdk
```

## Quick start

```typescript
import { UnlimitedMessagingApiClient } from "@unlimited-messaging/sdk";

const client = new UnlimitedMessagingApiClient({
  token: process.env.API_TOKEN,
});

// Send a WhatsApp message
const message = await client.message.messageControllerSend({
  recipient: "+33612345678",
  text: "Hello!",
});

console.log(message.id, message.status);
```

## Authentication

Get your API token from the [dashboard](https://app.unlimitedmessaging.app) and pass it as the `token` option.

```typescript
const client = new UnlimitedMessagingApiClient({
  token: "your_api_token",
});
```

## Examples

### Send a message

```typescript
const message = await client.message.messageControllerSend({
  recipient: "+33612345678",
  text: "Hello from the SDK!",
  simId: "optional-sim-id", // force a specific SIM
});
```

### List messages

```typescript
const result = await client.message.messageControllerFindAll({
  page: 1,
  limit: 20,
  status: "DELIVERED",   // PENDING | SENDING | SENT | DELIVERED | READ | FAILED
  search: "keyword",
});

console.log(`${result.total} messages`);
for (const msg of result.data) {
  console.log(msg.id, msg.status, msg.text);
}
```

### List linked SIMs

```typescript
const sims = await client.sim.simControllerGetLinkedSims();

for (const sim of sims) {
  console.log(sim.id, sim.phoneNumber);
}
```

## API reference

| Method | Description |
| --- | --- |
| `message.messageControllerSend` | Send a WhatsApp message |
| `message.messageControllerFindAll` | List messages with pagination and filters |
| `sim.simControllerGetLinkedSims` | List linked SIMs |

Official documentation: [docs.unlimitedmessaging.app](https://docs.unlimitedmessaging.app)

Full OpenAPI spec: [openapi.yaml](https://github.com/unlimitedmessaging/unlimited-messaging-sdk/blob/main/openapi.yaml)
