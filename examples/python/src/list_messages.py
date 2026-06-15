import os
from unlimited_messaging import UnlimitedMessagingApi

client = UnlimitedMessagingApi(
    base_url="https://api.unlimitedmessaging.app",
    token=os.environ["API_TOKEN"],
)

# List all messages (first page)
result = client.message.message_controller_find_all(page=1, limit=20)

print(f"{result.total} messages total (page {result.page}/{result.total_pages})")

for msg in result.data:
    print(f"[{msg.direction}] {msg.interlocutor} — {msg.status} — {msg.content}")

# Filter by status
failed = client.message.message_controller_find_all(
    page=1,
    limit=100,
    status="FAILED",
)

print(f"{failed.total} failed messages")
