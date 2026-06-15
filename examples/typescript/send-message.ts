import { UnlimitedMessagingApiClient } from "@unlimited-messaging/sdk";

const client = new UnlimitedMessagingApiClient({
  environment: "https://api.unlimitedmessaging.app",
  token: process.env.API_TOKEN!,
});

// Send a WhatsApp message
const message = await client.message.messageControllerSend({
  recipient: "+33612345678",
  text: "Hello from the SDK!",
});

console.log(`Message sent — id: ${message.body.id}, status: ${message.body.status}`);

// Send with a specific SIM
const messageWithSim = await client.message.messageControllerSend({
  recipient: "+33612345678",
  text: "Hello from a specific SIM!",
  simId: "sim_abc123",
});

console.log(`Sent via SIM ${messageWithSim.body.simId}`);
