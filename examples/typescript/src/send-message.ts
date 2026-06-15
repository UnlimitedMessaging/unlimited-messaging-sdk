import { UnlimitedMessagingApiClient } from "@unlimited-messaging/sdk";

const client = new UnlimitedMessagingApiClient({
  environment: "https://api.unlimitedmessaging.app",
  token: process.env.API_TOKEN!,
});

// Get all sims
const sims = await client.sim.getLinkedSims();
const sim = sims[0] ?? null;

if (sim === null) {
  throw new Error("No sim to send a message");
}

// Send a WhatsApp message via a specific SIM
const message = await client.message.send({
  recipient: "+33612345678",
  text: "Hello from the SDK!",
  simId: sim.id,
});

console.log(`Message sent — id: ${message.id}, status: ${message.status}`);

// Check all messages
const messages = await client.message.findAll({ page: 1, limit: 10 });
console.log(`Total messages: ${messages.data.length}`);

// Check sent messages status after 5 seconds
setTimeout(() => {
  async function checkMessage(messageId: string) {
    const message = await client.message.findOne(messageId)
    console.log(`Message status: ${message.status}`)
  }

  checkMessage(message.id)
})