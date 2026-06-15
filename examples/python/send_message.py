import os
from unlimited_messaging import UnlimitedMessagingApi

client = UnlimitedMessagingApi(
    base_url="https://api.unlimited-messaging.com",
    token=os.environ["API_TOKEN"],
)

# Send a WhatsApp message
message = client.message.message_controller_send(
    recipient="+33612345678",
    text="Hello from the SDK!",
)

print(f"Message sent — id: {message.id}, status: {message.status}")

# Send with a specific SIM
message_with_sim = client.message.message_controller_send(
    recipient="+33612345678",
    text="Hello from a specific SIM!",
    sim_id="sim_abc123",
)

print(f"Sent via SIM {message_with_sim.sim_id}")
