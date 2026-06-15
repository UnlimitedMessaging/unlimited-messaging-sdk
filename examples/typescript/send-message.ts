import { UnlimitedMessagingApiClient } from "@unlimited-messaging/sdk";

const client = new UnlimitedMessagingApiClient({
  token: process.env.API_TOKEN!,
});

// Send a WhatsApp message
const message = await client.message.messageControllerSend({
  recipient: "+33612345678",
  text: "Hello from the SDK!",
});

console.log(`Message sent — id: ${message.id}, status: ${message.status}`);

// Send with a specific SIM



// Get all sims :
const sims = await client.sim.simControllerGetLinkedSims();
const sim = sims[0] ?? null

if (sim === null) {
  throw new Error("No sim to send a message")
}

const messageWithSim = await client.message.messageControllerSend({
  recipient: "+33612345678",
  text: "Hello from a specific SIM!",
  simId: sim.id,
});

console.log(`Sent via SIM ${messageWithSim.simId}`);
