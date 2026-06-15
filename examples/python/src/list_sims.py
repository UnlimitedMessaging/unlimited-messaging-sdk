import os
from unlimited_messaging import UnlimitedMessagingApi

client = UnlimitedMessagingApi(
    base_url="https://api.unlimitedmessaging.app",
    token=os.environ["API_TOKEN"],
)

# Get all linked SIMs
sims = client.sim.get_linked_sims()

for sim in sims:
    print(f"SIM {sim.id} — {sim.phone or 'no phone'}")
