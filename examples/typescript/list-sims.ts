import { UnlimitedMessagingApiClient } from "@unlimited-messaging/sdk";

const client = new UnlimitedMessagingApiClient({
  environment: "https://api.unlimited-messaging.com",
  token: process.env.API_TOKEN!,
});

// Get all linked SIMs
const sims = await client.sim.simControllerGetLinkedSims();

for (const sim of sims.body) {
  console.log(`SIM ${sim.id} — ${sim.phone ?? "no phone"} — ${sim.status} (${sim.type})`);
}

// Find an active SIM to use
const activeSim = sims.body.find((s: { status: string }) => s.status === "ACTIVE");
if (activeSim) {
  console.log(`Using SIM: ${activeSim.id}`);
}
