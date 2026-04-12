# PearBlog Engine — API Documentation

> **Version:** 6.0  
> **Base URL:** `https://your-site.com/wp-json/pearblog/v1`  
> **Authentication:** `X-PearBlog-API-Key` header  
> **Format:** All requests and responses use `application/json`  

---

## Authentication

All endpoints (except `/health`) require a valid API key in the request header:

```http
X-PearBlog-API-Key: your-api-key-here
```

Generate your key in **WP Admin → PearBlog Engine → Settings → General → REST API Key**.

### WordPress Application Passwords (alternative)

You may also use WordPress Application Passwords with Basic Auth:

```http
Authorization: Basic base64(username:app-password)
```

### Authentication Errors

| HTTP Status | Code | Description |
|-------------|------|-------------|
| `401` | `rest_forbidden` | Missing or invalid API key |
| `403` | `rest_cannot_manage` | User lacks `manage_options` capability |

---

## Rate Limits

| Endpoint Category | Limit | Window | Headers |
|-------------------|-------|--------|---------|
| Health check | Unlimited | — | — |
| Read (GET) | 120 req | 60 s | `X-RateLimit-Remaining` |
| Write (POST/DELETE) | 30 req | 60 s | `X-RateLimit-Remaining` |
| Pipeline trigger | 5 req | 60 s | `X-RateLimit-Remaining` |

When a rate limit is exceeded, the API responds with:

```json
HTTP 429 Too Many Requests
Retry-After: 15

{
  "code": "rate_limit_exceeded",
  "message": "Too many requests. Please wait 15 seconds.",
  "data": { "retry_after": 15 }
}
```

---

## Error Responses

All errors follow this format:

```json
{
  "code": "error_code",
  "message": "Human-readable error message.",
  "data": { "status": 400 }
}
```

### Standard Error Codes

| Code | HTTP Status | Description |
|------|-------------|-------------|
| `rest_forbidden` | 401 | Authentication required |
| `rest_cannot_manage` | 403 | Insufficient permissions |
| `invalid_param` | 400 | Invalid or missing parameter |
| `not_found` | 404 | Resource not found |
| `rate_limit_exceeded` | 429 | Rate limit exceeded |
| `pipeline_running` | 409 | A pipeline run is already in progress |
| `circuit_open` | 503 | AI circuit breaker is open; try again later |
| `internal_error` | 500 | Unexpected server error |

---

## Endpoints

---

### `GET /health`

Returns the overall health status of the PearBlog Engine instance. **No authentication required.**

#### Response `200 OK`

```json
{
  "status": "ok",
  "checks": {
    "openai_connected": true,
    "queue_size": 12,
    "last_pipeline_run": "2026-04-12T18:45:00Z",
    "circuit_breaker": "closed",
    "ai_cost_cents_today": 850,
    "memory_usage_mb": 42.3,
    "db_queries_last_run": 24,
    "plugin_version": "6.0.0"
  },
  "timestamp": "2026-04-12T19:00:00Z"
}
```

`status` values:
- `"ok"` — all systems operational
- `"degraded"` — some non-critical checks failing
- `"down"` — critical failure (OpenAI unreachable, circuit open)

---

### `GET /topics`

List all topics currently in the generation queue.

#### Response `200 OK`

```json
{
  "topics": [
    "10 best hiking trails in Colorado",
    "Beginner guide to intermittent fasting",
    "How to start a podcast in 2026"
  ],
  "count": 3
}
```

---

### `POST /topics`

Add one or more topics to the generation queue.

#### Request Body

```json
{
  "topics": [
    "Best yoga poses for beginners",
    "Mediterranean diet meal plan"
  ]
}
```

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `topics` | `string[]` | ✅ | Array of topic strings (1–100 items) |

#### Response `201 Created`

```json
{
  "added": 2,
  "queue_size": 5
}
```

#### Error `400 Bad Request`

```json
{
  "code": "invalid_param",
  "message": "topics must be a non-empty array of strings."
}
```

---

### `DELETE /topics`

Clear the entire topic queue.

#### Response `200 OK`

```json
{
  "cleared": 5
}
```

---

### `GET /webhooks`

List all registered outbound webhooks.

#### Response `200 OK`

```json
{
  "webhooks": [
    {
      "id": 1,
      "url": "https://example.com/webhook",
      "events": ["pearblog.article_published", "pearblog.quality_scored"],
      "created_at": "2026-04-01T10:00:00Z",
      "last_triggered": "2026-04-12T09:15:00Z",
      "failure_count": 0
    }
  ],
  "count": 1
}
```

---

### `POST /webhooks`

Register a new outbound webhook.

#### Request Body

```json
{
  "url": "https://example.com/webhook",
  "events": ["pearblog.article_published"],
  "secret": "my-hmac-secret"
}
```

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `url` | `string` | ✅ | HTTPS endpoint URL |
| `events` | `string[]` | ✅ | Event names to subscribe to |
| `secret` | `string` | ❌ | HMAC-SHA256 signing secret |

#### Supported Webhook Events

| Event | Triggered when |
|-------|----------------|
| `pearblog.article_published` | A post is published through the pipeline |
| `pearblog.quality_scored` | Quality score assigned to a post |
| `pearblog.content_refreshed` | An existing post is refreshed via ContentRefreshEngine |
| `pearblog.pipeline_error` | Pipeline encounters a non-recoverable error |
| `pearblog.circuit_opened` | AI circuit breaker opens |

#### Response `201 Created`

```json
{
  "id": 2,
  "url": "https://example.com/webhook",
  "events": ["pearblog.article_published"],
  "created_at": "2026-04-12T20:00:00Z"
}
```

---

### `DELETE /webhooks/{id}`

Remove a registered webhook.

#### Path Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `id` | `integer` | Webhook ID |

#### Response `200 OK`

```json
{
  "deleted": true,
  "id": 2
}
```

#### Error `404 Not Found`

```json
{
  "code": "not_found",
  "message": "Webhook with ID 2 not found."
}
```

---

### `GET /calendar`

List all scheduled content calendar entries.

#### Query Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `month` | `string` | Filter by month, format `YYYY-MM` |
| `status` | `string` | Filter by status: `pending`, `queued`, `published` |

#### Response `200 OK`

```json
{
  "entries": [
    {
      "date": "2026-05-01",
      "topic": "May Day: Best hiking trails",
      "status": "pending"
    },
    {
      "date": "2026-05-15",
      "topic": "Spring fitness guide",
      "status": "queued"
    }
  ],
  "count": 2
}
```

---

### `POST /calendar`

Schedule a topic for a specific date.

#### Request Body

```json
{
  "date": "2026-06-01",
  "topic": "Summer travel destinations"
}
```

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `date` | `string` | ✅ | ISO date `YYYY-MM-DD`; must be today or future |
| `topic` | `string` | ✅ | Topic string (max 300 characters) |

#### Response `201 Created`

```json
{
  "date": "2026-06-01",
  "topic": "Summer travel destinations",
  "status": "pending"
}
```

---

### `DELETE /calendar/{date}`

Remove a scheduled calendar entry.

#### Path Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `date` | `string` | ISO date `YYYY-MM-DD` |

#### Response `200 OK`

```json
{
  "deleted": true,
  "date": "2026-06-01"
}
```

---

### `GET /performance/metrics`

Return recent pipeline performance metrics from PerformanceDashboard.

#### Query Parameters

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `limit` | `integer` | `50` | Max entries to return (1–200) |

#### Response `200 OK`

```json
{
  "metrics": [
    {
      "topic": "10 best hiking trails",
      "duration_ms": 10450,
      "memory_peak_mb": 48.2,
      "db_queries": 28,
      "ai_tokens": 1842,
      "status": "success",
      "timestamp": "2026-04-12T18:45:00Z"
    }
  ],
  "summary": {
    "avg_duration_ms": 10200,
    "success_rate": 0.97,
    "total_runs": 145,
    "p95_duration_ms": 16800
  }
}
```

---

### `POST /pipeline/trigger`

Manually trigger a pipeline run for a single topic.

> ⚠️ **Rate limit:** 5 requests per 60 seconds.

#### Request Body

```json
{
  "topic": "Best sourdough bread recipes",
  "dry_run": false
}
```

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `topic` | `string` | ✅ | Topic to generate content for |
| `dry_run` | `boolean` | ❌ | If `true`, validates without publishing |

#### Response `202 Accepted`

```json
{
  "status": "queued",
  "topic": "Best sourdough bread recipes",
  "run_id": "pearblog_run_20260412_1900"
}
```

#### Error `503 Service Unavailable` (circuit open)

```json
{
  "code": "circuit_open",
  "message": "AI circuit breaker is open. Retry after 2026-04-12T19:05:00Z.",
  "data": { "retry_after": "2026-04-12T19:05:00Z" }
}
```

---

## Webhook Payload Format

All outbound webhook requests use this envelope:

```http
POST https://your-endpoint.com/webhook
Content-Type: application/json
X-PearBlog-Event: pearblog.article_published
X-PearBlog-Signature: sha256=<hmac-hex>
X-PearBlog-Timestamp: 1712944800
```

```json
{
  "event": "pearblog.article_published",
  "timestamp": 1712944800,
  "site_url": "https://your-site.com",
  "data": {
    "post_id": 142,
    "post_title": "10 Best Hiking Trails in Colorado",
    "post_url": "https://your-site.com/hiking-trails-colorado/",
    "topic": "10 best hiking trails in Colorado",
    "quality_score": 87,
    "ai_image_attached": true,
    "duration_ms": 10450
  }
}
```

### Signature Verification

```php
$payload   = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_PEARBLOG_SIGNATURE'] ?? '';
$secret    = 'your-webhook-secret';

$expected = 'sha256=' . hash_hmac('sha256', $payload, $secret);
if (!hash_equals($expected, $signature)) {
    http_response_code(403);
    exit;
}
```

---

## OpenAPI Specification (Excerpt)

```yaml
openapi: "3.0.3"
info:
  title: PearBlog Engine API
  version: "6.0"
  description: REST API for the PearBlog Engine WordPress plugin.
servers:
  - url: https://your-site.com/wp-json/pearblog/v1
    description: Production

security:
  - ApiKeyAuth: []

components:
  securitySchemes:
    ApiKeyAuth:
      type: apiKey
      in: header
      name: X-PearBlog-API-Key

paths:
  /health:
    get:
      summary: Health check
      security: []
      responses:
        "200":
          description: Health status
          content:
            application/json:
              schema:
                type: object
                properties:
                  status:
                    type: string
                    enum: [ok, degraded, down]

  /topics:
    get:
      summary: List topics
      responses:
        "200":
          description: Topic list
    post:
      summary: Add topics
      requestBody:
        required: true
        content:
          application/json:
            schema:
              type: object
              required: [topics]
              properties:
                topics:
                  type: array
                  items:
                    type: string
      responses:
        "201":
          description: Topics added

  /webhooks:
    get:
      summary: List webhooks
      responses:
        "200":
          description: Webhook list
    post:
      summary: Create webhook
      requestBody:
        required: true
        content:
          application/json:
            schema:
              type: object
              required: [url, events]
              properties:
                url:
                  type: string
                  format: uri
                events:
                  type: array
                  items:
                    type: string
                secret:
                  type: string
      responses:
        "201":
          description: Webhook created
```

---

## Postman Collection

Import the collection from the link below or paste the JSON into Postman:

> **Download:** `examples/postman/PearBlog-Engine-v6.postman_collection.json`

### Quick Import via Postman

1. Open Postman
2. Click **Import** → **Link**
3. Paste: `https://raw.githubusercontent.com/AndyPearman89/PearBlog-Engine-/main/examples/postman/PearBlog-Engine-v6.postman_collection.json`

### Environment Variables (Postman)

| Variable | Value |
|----------|-------|
| `base_url` | `https://your-site.com` |
| `api_key` | `your-api-key` |

---

## SDK / Client Libraries

| Language | Location | Install |
|----------|----------|---------|
| JavaScript | `clients/js/pearblog-client.js` | Copy into project |
| Python | `clients/python/pearblog_client.py` | `pip install requests` + copy |

See `clients/` directory for full documentation and examples.

---

## Changelog

| Version | Date | Changes |
|---------|------|---------|
| 6.0 | 2026-04-12 | Initial public API release |
| 5.2 | 2026-02-01 | Added health endpoint, webhook signing |
| 5.0 | 2025-11-01 | REST API introduced |
