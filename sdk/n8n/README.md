# n8n-nodes-unlimited-messaging

Official [n8n](https://n8n.io) community node for the [Unlimited Messaging](https://unlimitedmessaging.app) API — send and receive WhatsApp messages directly in your n8n workflows.

[![npm version](https://img.shields.io/npm/v/n8n-nodes-unlimited-messaging?style=flat-square&color=cb0000)](https://www.npmjs.com/package/n8n-nodes-unlimited-messaging)
[![npm downloads](https://img.shields.io/npm/dm/n8n-nodes-unlimited-messaging?style=flat-square&color=cb0000)](https://www.npmjs.com/package/n8n-nodes-unlimited-messaging)
[![npm total downloads](https://img.shields.io/npm/dt/n8n-nodes-unlimited-messaging?style=flat-square&label=total%20downloads&color=cb0000)](https://www.npmjs.com/package/n8n-nodes-unlimited-messaging)
[![Bundle size](https://img.shields.io/bundlephobia/minzip/n8n-nodes-unlimited-messaging?style=flat-square&label=gzipped)](https://bundlephobia.com/package/n8n-nodes-unlimited-messaging)
[![CI](https://img.shields.io/github/actions/workflow/status/unlimitedmessaging/unlimited-messaging-sdk/release.yml?style=flat-square&label=CI)](https://github.com/unlimitedmessaging/unlimited-messaging-sdk/actions/workflows/release.yml)
[![License](https://img.shields.io/npm/l/n8n-nodes-unlimited-messaging?style=flat-square)](https://github.com/unlimitedmessaging/unlimited-messaging-sdk/blob/main/LICENSE)
[![Socket](https://socket.dev/api/badge/npm/package/n8n-nodes-unlimited-messaging)](https://socket.dev/npm/package/n8n-nodes-unlimited-messaging)

- **Website:** [unlimitedmessaging.app](https://unlimitedmessaging.app)
- **Docs:** [docs.unlimitedmessaging.app](https://docs.unlimitedmessaging.app)
- **API reference:** [api.unlimitedmessaging.app](https://api.unlimitedmessaging.app)
- **GitHub:** [unlimitedmessaging/unlimited-messaging-sdk](https://github.com/unlimitedmessaging/unlimited-messaging-sdk)
- **npm:** [n8n-nodes-unlimited-messaging](https://www.npmjs.com/package/n8n-nodes-unlimited-messaging)


## Installation

In your n8n instance, go to **Settings → Community Nodes** and install:

```text
n8n-nodes-unlimited-messaging
```

Or with the n8n CLI:

```bash
n8n community install n8n-nodes-unlimited-messaging
```

## Credentials

1. Go to [app.unlimitedmessaging.app](https://app.unlimitedmessaging.app) and generate an API token.
2. In n8n, add a new credential of type **Unlimited Messaging API** and paste your token.
3. Click **Test** to verify it works — it calls `GET /sim` and returns a 200 if the token is valid.

## Supported operations

### Message

| Operation | Description                                               |
| --------- | --------------------------------------------------------- |
| **Send**  | Send a WhatsApp message to a phone number                 |
| **Get**   | Retrieve a message by ID                                  |
| **List**  | List messages with optional filters (status, SIM, search) |

### SIM

| Operation | Description                                   |
| --------- | --------------------------------------------- |
| **List**  | List all linked WhatsApp accounts (SIM cards) |

## Send a message

```text
Resource:    Message
Operation:   Send
Recipient:   +33612345678
Text:        Hello from n8n!
SIM ID:      (optional — leave empty to auto-select)
```

## List messages

```text
Resource:    Message
Operation:   List
Return All:  true         ← auto-paginates through all pages
Filters:
  Status:    DELIVERED
  Search:    invoice
```

## Links

- [Unlimited Messaging Dashboard](https://app.unlimitedmessaging.app)
- [API Documentation](https://docs.unlimitedmessaging.app)
- [npm package](https://www.npmjs.com/package/n8n-nodes-unlimited-messaging)
- [Source code](https://github.com/unlimitedmessaging/unlimited-messaging-sdk/tree/main/sdk/n8n)
- [Report an issue](https://github.com/unlimitedmessaging/unlimited-messaging-sdk/issues)

## License

MIT
