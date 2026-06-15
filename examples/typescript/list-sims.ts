import { UnlimitedMessagingApiClient } from "@unlimited-messaging/sdk";

const client = new UnlimitedMessagingApiClient({
  environment: "https://api.unlimitedmessaging.app",
  token: process.env.API_TOKEN!,
});

// Get all linked SIMs
const sims = await client.sim.simControllerGetLinkedSims();

for (const sim of sims) {
  console.log(`SIM ${sim.id} — ${sim.phone ?? "no phone"}`);
}
