import { UnlimitedMessagingApiClient } from "@unlimited-messaging/sdk";

const client = new UnlimitedMessagingApiClient({
  environment: "https://api.unlimitedmessaging.app",
  token: process.env.API_TOKEN!,
});

// List all messages (first page)
const result = await client.message.findAll({
  page: 1,
  limit: 20,
});

console.log(`${result.total} messages total (page ${result.page}/${result.totalPages})`);

for (const msg of result.data) {
  console.log(`[${msg.direction}] ${msg.interlocutor} — ${msg.status} — ${msg.content}`);
}

// Filter by status
const failed = await client.message.findAll({
  page: 1,
  limit: 100,
  status: "FAILED",
});

console.log(`${failed.total} failed messages`);
