import os
import time
from unlimited_messaging import UnlimitedMessagingApi

client = UnlimitedMessagingApi(
    base_url="https://api.unlimitedmessaging.app",
    token=os.environ["API_TOKEN"],
)

# Send a WhatsApp message
message = client.message.send(
    recipient="+33612345678",
    text="Hello from the SDK!",
)

print(f"Message sent — id: {message.id}, status: {message.status}")

all_sims = client.sim.get_linked_sims()
if len(all_sims) < 1:
    raise Exception('No sim active')

sim = all_sims[0]

# Send with a specific SIM
message_with_sim = client.message.send(
    recipient="+33612345678",
    text="Hello from a specific SIM!",
    sim_id=sim.id,
)

print(f"Sent via SIM {message_with_sim.sim_id}")

time.sleep(5)
message = client.message.find_one(message_with_sim.id)
print(f"Message status: ${message.status}")
