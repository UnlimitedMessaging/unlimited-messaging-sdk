# Changelog

All notable changes to this project will be documented in this file.

## [0.1.7] - 2026-07-17

SDK update.

## [0.1.4] - 2026-07-16

*   **New Endpoints**
    *   `GET /sim/link`: Start a WhatsApp linking session.
    *   `GET /sim/{simId}/qrcode`: Get the current QR code for a SIM.
    *   `GET /sim/{simId}`: Get a SIM by ID.
    *   `PATCH /sim/{simId}`: Rename a SIM.
    *   `DELETE /sim/{simId}`: Deactivate a SIM.
    *   `POST /api-keys`: Create an API key.
    *   `GET /api-keys`: List API keys.
    *   `DELETE /api-keys/{id}`: Revoke an API key.

*   **Changed Parameters**
    *   `GET /message`:
        *   Added `channel` query parameter (enum: `WHATSAPP`, `SMS`, `EMAIL`) to filter messages.
        *   Added `direction` query parameter (enum: `IN`, `OUT`) to filter messages.
        *   The `status` query parameter now includes `UNDELIVERABLE` as a possible value.
    *   `POST /message`:
        *   The `recipient` request body field now validates for E.164 phone number format (`^\+?\d{6,15}$`).
        *   The `simId` request body field now explicitly allows `null`, enabling automatic SIM resolution if omitted.

*   **New Response Fields**
    *   Message objects (returned by `GET /message`, `POST /message`, `GET /message/{id}`) now include:
        *   `error`: `string | null` - Provides additional error details.
        *   `channel`: `string` (enum: `WHATSAPP`, `SMS`, `EMAIL`) - Indicates the communication channel used.
        *   The `status` field for messages now includes `UNDELIVERABLE` as a possible value.
    *   SIM objects (returned by `GET /sim`, `GET /sim/{simId}`, `PATCH /sim/{simId}`) now include:
        *   `type`: `string` (enum: `SYSTEM`, `USER`) - Specifies if the SIM is a system-managed or user-linked SIM.
        *   `status`: `string` (enum: `ACTIVE`, `INACTIVE`, `LINKING`, `DUPLICATE_PHONE`, `BLOCKED`) - The current operational status of the SIM.
        *   `blockedUntil`: `string | null` (date-time) - Timestamp indicating when a SIM's block will expire, if applicable.
        *   The `phone` and `name` fields for SIM objects now explicitly allow `null`.
    *   New API Key objects (returned by `POST /api-keys`, `GET /api-keys`) now include:
        *   `id`: `string` - Unique identifier for the API key.
        *   `name`: `string` - User-defined name for the API key.
        *   `prefix`: `string` - The visible prefix of the API key.
        *   `expiresAt`: `string | null` (date-time) - Expiration date of the key, if set.
        *   `lastUsedAt`: `string | null` (date-time) - Timestamp of the last time the key was used.
        *   `revokedAt`: `string | null` (date-time) - Timestamp if the key has been revoked.
        *   `createdAt`: `string` (date-time) - Creation timestamp of the API key.
        *   `updatedAt`: `string` (date-time) - Last update timestamp of the API key.
        *   `key`: `string` - The full API key (only returned when creating a new key via `POST /api-keys`).

## [0.1.3] - 2026-07-02

SDK update.

## [0.1.2] - 2026-06-15

### Added

- TypeScript and Python SDK generated from OpenAPI spec
- `message.send` — send a WhatsApp message
- `message.findAll` — list messages with pagination and filters
- `message.findOne` — get a single message by ID
- `sim.getLinkedSims` — list linked SIMs
- Async support for Python (`AsyncUnlimitedMessagingApi`)
