# @unlimited-messaging/sdk

Official TypeScript / Node.js client for the [Unlimited Messaging](https://unlimitedmessaging.app) API — send and receive WhatsApp messages programmatically.

[![npm version](https://img.shields.io/npm/v/@unlimited-messaging/sdk?style=flat-square&color=cb0000)](https://www.npmjs.com/package/@unlimited-messaging/sdk)
[![npm downloads](https://img.shields.io/npm/dm/@unlimited-messaging/sdk?style=flat-square&color=cb0000)](https://www.npmjs.com/package/@unlimited-messaging/sdk)
[![npm total downloads](https://img.shields.io/npm/dt/@unlimited-messaging/sdk?style=flat-square&label=total%20downloads&color=cb0000)](https://www.npmjs.com/package/@unlimited-messaging/sdk)
[![Bundle size](https://img.shields.io/bundlephobia/minzip/@unlimited-messaging/sdk?style=flat-square&label=gzipped)](https://bundlephobia.com/package/@unlimited-messaging/sdk)
[![CI](https://img.shields.io/github/actions/workflow/status/unlimitedmessaging/unlimited-messaging-sdk/release.yml?style=flat-square&label=CI)](https://github.com/unlimitedmessaging/unlimited-messaging-sdk/actions/workflows/release.yml)
[![License](https://img.shields.io/npm/l/@unlimited-messaging/sdk?style=flat-square)](https://github.com/unlimitedmessaging/unlimited-messaging-sdk/blob/main/LICENSE)
[![Socket](https://socket.dev/api/badge/npm/package/@unlimited-messaging/sdk)](https://socket.dev/npm/package/@unlimited-messaging/sdk)

- **Website:** [unlimitedmessaging.app](https://unlimitedmessaging.app)
- **Docs:** [docs.unlimitedmessaging.app](https://docs.unlimitedmessaging.app)
- **API reference:** [api.unlimitedmessaging.app](https://api.unlimitedmessaging.app)
- **GitHub:** [unlimitedmessaging/unlimited-messaging-sdk](https://github.com/unlimitedmessaging/unlimited-messaging-sdk)
- **npm:** [@unlimited-messaging/sdk](https://www.npmjs.com/package/@unlimited-messaging/sdk)

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
const message = await client.message.send({
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
const message = await client.message.send({
  recipient: "+33612345678",
  text: "Hello from the SDK!",
  simId: "optional-sim-id", // force a specific SIM
});
```

### List messages

```typescript
const result = await client.message.findAll({
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

### Get a single message

```typescript
const msg = await client.message.findOne("message-id");
console.log(msg.id, msg.status, msg.text);
```

### List linked SIMs

```typescript
const sims = await client.sim.getLinkedSims();

for (const sim of sims) {
  console.log(sim.id, sim.phoneNumber);
}
```

## API reference

| Method              | Description                               |
| ------------------- | ----------------------------------------- |
| `message.send`      | Send a WhatsApp message                   |
| `message.findAll`   | List messages with pagination and filters |
| `message.findOne`   | Get a single message by ID                |
| `sim.getLinkedSims` | List linked SIMs                          |

Official documentation: [docs.unlimitedmessaging.app](https://docs.unlimitedmessaging.app)

Full OpenAPI spec: [openapi.yaml](https://github.com/unlimitedmessaging/unlimited-messaging-sdk/blob/main/openapi.yaml)
