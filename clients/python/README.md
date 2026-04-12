# PearBlog Engine — Python API Client

## Requirements

```bash
pip install requests
```

## Usage

```python
from pearblog_client import PearBlogClient, PearBlogAPIError

client = PearBlogClient(
    base_url="https://your-site.com",
    api_key="your-pearblog-api-key",
)

# Check site health
health = client.health()
print(health["status"])  # 'ok' | 'degraded'

# Add topics to the queue
client.add_topics([
    "10 best hiking trails in Colorado",
    "Beginner guide to intermittent fasting",
])

# Get all queued topics
topics = client.get_topics()
print(topics)

# Schedule a post for a specific date
entry = client.schedule_post("2026-05-20", "How to start a podcast in 2026")
print(entry)

# Register a webhook
webhook = client.create_webhook(
    url="https://example.com/webhook",
    events=["pearblog.article_published", "pearblog.quality_scored"],
    secret="my-signing-secret",
)
print(webhook)

# Get performance metrics
metrics = client.get_metrics()
print(f"Average pipeline duration: {metrics['avg_duration_ms']} ms")

# Error handling
try:
    client.health()
except PearBlogAPIError as exc:
    print(f"Error {exc.status_code}: {exc}")
```

## Webhook Signature Verification

```python
from flask import Flask, request, abort
from pearblog_client import PearBlogClient

app = Flask(__name__)
WEBHOOK_SECRET = "my-signing-secret"

@app.route("/webhook", methods=["POST"])
def receive_webhook():
    valid = PearBlogClient.verify_webhook_signature(
        payload=request.get_data(),
        signature=request.headers.get("X-PearBlog-Signature", ""),
        secret=WEBHOOK_SECRET,
    )
    if not valid:
        abort(403)

    event = request.json
    print(f"Received event: {event}")
    return "", 200
```

## API Reference

| Method | Description |
|--------|-------------|
| `health()` | GET /pearblog/v1/health |
| `get_topics()` | GET /pearblog/v1/topics |
| `add_topics(topics)` | POST /pearblog/v1/topics |
| `clear_topics()` | DELETE /pearblog/v1/topics |
| `get_webhooks()` | GET /pearblog/v1/webhooks |
| `create_webhook(url, events, secret)` | POST /pearblog/v1/webhooks |
| `delete_webhook(id)` | DELETE /pearblog/v1/webhooks/{id} |
| `get_calendar(**params)` | GET /pearblog/v1/calendar |
| `schedule_post(date, topic)` | POST /pearblog/v1/calendar |
| `remove_scheduled_post(date)` | DELETE /pearblog/v1/calendar/{date} |
| `get_metrics()` | GET /pearblog/v1/performance/metrics |
| `verify_webhook_signature(payload, signature, secret)` | Static helper |
