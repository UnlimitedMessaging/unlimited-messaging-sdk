import os
from unlimited_messaging import UnlimitedMessagingApi

client = UnlimitedMessagingApi(
    base_url="https://api.unlimited-messaging.com",
    token=os.environ["API_TOKEN"],
)

# Get all linked SIMs
sims = client.sim.sim_controller_get_linked_sims()

for sim in sims:
    print(f"SIM {sim.id} — {sim.phone or 'no phone'} — {sim.status} ({sim.type})")

# Find an active SIM to use
active_sim = next((s for s in sims if s.status == "ACTIVE"), None)
if active_sim:
    print(f"Using SIM: {active_sim.id}")
