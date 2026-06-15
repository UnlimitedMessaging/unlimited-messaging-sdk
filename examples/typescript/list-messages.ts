import { UnlimitedMessagingApiClient } from "@unlimited-messaging/sdk";

const client = new UnlimitedMessagingApiClient({
  environment: "https://api.unlimited-messaging.com",
  token: process.env.API_TOKEN!,
});

// List all messages (first page)
const result = await client.message.messageControllerFindAll({
  page: 1,
  limit: 20,
});

console.log(`${result.body.total} messages total (page ${result.body.page}/${result.body.totalPages})`);

for (const msg of result.body.data) {
  console.log(`[${msg.direction}] ${msg.interlocutor} — ${msg.status} — ${msg.content}`);
}

// Filter by status
const failed = await client.message.messageControllerFindAll({
  page: 1,
  limit: 100,
  status: "FAILED",
});

console.log(`${failed.body.total} failed messages`);
