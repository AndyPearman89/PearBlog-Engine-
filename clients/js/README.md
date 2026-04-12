# PearBlog Engine — JavaScript API Client

## Installation

Copy `pearblog-client.js` into your project, or import directly:

```js
import { PearBlogClient } from './pearblog-client.js';
```

For Node.js (< 18 without native fetch):
```bash
npm install node-fetch
```
Then add at the top of your script:
```js
import fetch from 'node-fetch';
globalThis.fetch = fetch;
```

## Usage

```js
import { PearBlogClient } from './pearblog-client.js';

const client = new PearBlogClient(
  'https://your-site.com',
  'your-pearblog-api-key'
);

// Check site health
const health = await client.health();
console.log(health.status); // 'ok' | 'degraded'

// Add topics to queue
await client.addTopics(['10 best hiking trails in Colorado']);

// Get all queued topics
const topics = await client.getTopics();

// Schedule a post for a specific date
await client.schedulePost('2026-05-15', 'Best yoga poses for beginners');

// Register a webhook
await client.createWebhook(
  'https://example.com/webhook',
  ['pearblog.article_published', 'pearblog.quality_scored'],
  'my-secret-key'
);

// Get pipeline performance metrics
const metrics = await client.getMetrics();
console.log(metrics.avg_duration_ms);
```

## API Reference

| Method | Description |
|--------|-------------|
| `health()` | GET /pearblog/v1/health |
| `getTopics()` | GET /pearblog/v1/topics |
| `addTopics(topics)` | POST /pearblog/v1/topics |
| `clearTopics()` | DELETE /pearblog/v1/topics |
| `getWebhooks()` | GET /pearblog/v1/webhooks |
| `createWebhook(url, events, secret)` | POST /pearblog/v1/webhooks |
| `deleteWebhook(id)` | DELETE /pearblog/v1/webhooks/{id} |
| `getCalendar(params)` | GET /pearblog/v1/calendar |
| `schedulePost(date, topic)` | POST /pearblog/v1/calendar |
| `removeScheduledPost(date)` | DELETE /pearblog/v1/calendar/{date} |
| `getMetrics()` | GET /pearblog/v1/performance/metrics |

## Authentication

All requests include the `X-PearBlog-API-Key` header.  
Generate your API key in **WP Admin → PearBlog Engine → Settings → General → API Key**.
