# ⚙️ PearBlog Engine — WordPress MU-Plugin

> Autonomous AI content production engine for WordPress.

---

## Quick Start

Drop `mu-plugins/pearblog-engine/` into your WordPress `wp-content/mu-plugins/` directory.
The plugin bootstraps automatically — no activation required.

---

## Architecture

```
pearblog-engine.php              # Bootstrap & PSR-4 autoloader
└── src/
    ├── Core/Plugin.php          # Singleton boot — registers all sub-systems
    ├── Pipeline/ContentPipeline # 7-step autonomous content generation
    ├── AI/                      # OpenAI GPT-4o-mini + DALL-E 3
    ├── Content/                 # Prompt builders, validator, scorer, queue
    ├── SEO/SEOEngine            # Meta tags & Schema.org injection
    ├── Monetization/            # AdSense + Affiliate + SaaS CTA
    ├── Scheduler/CronManager    # WP-Cron hook registration
    ├── Keywords/KeywordCluster  # Keyword grouping value object
    ├── API/AutomationController # REST API for external automation
    ├── Admin/AdminPage          # Settings UI & topic queue management
    └── Tenant/                  # WordPress Multisite context
```

---

## Modules

### Core (`Core/Plugin.php`)

Singleton that boots all sub-systems exactly once via `Plugin::boot()`.
Hooks into `rest_api_init`, `init`, and `admin_menu`.

### Pipeline (`Pipeline/ContentPipeline.php`)

Orchestrates the 7-step autonomous flow (hourly via WP-Cron):

1. Pop topic from `TopicQueue`
2. Select `PromptBuilder` via `PromptBuilderFactory`
3. Generate content via `AIClient` (GPT-4o-mini)
4. Apply SEO optimization via `SEOEngine`
5. Inject monetization via `MonetizationEngine`
6. Generate featured image via `ImageGenerator` (DALL-E 3)
7. Publish as WordPress post

~55 seconds per article, ~$0.08 per article (with image).

### AI (`AI/`)

| Class | Description |
|-------|-------------|
| `AIClient` | Thin wrapper around OpenAI Chat Completions API |
| `ImageGenerator` | DALL-E 3 image generation with 4 visual styles (photorealistic, illustration, artistic, minimal) |

### Content (`Content/`)

| Class | Description |
|-------|-------------|
| `PromptBuilder` | Base prompt builder with SEO and monetization rules |
| `PromptBuilderFactory` | Auto-selects builder based on industry keywords |
| `TravelPromptBuilder` | Travel content with mandatory sections (extends PromptBuilder) |
| `BeskidyPromptBuilder` | Beskidy-specific with weather and day planning (extends TravelPromptBuilder) |
| `MultiLanguageTravelBuilder` | PL/EN/DE localization (extends BeskidyPromptBuilder) |
| `ContentValidator` | Validates structure and quality in 3 modes (generic/travel/beskidy) |
| `ContentScore` | Immutable value object for content scoring results |
| `TopicQueue` | FIFO topic queue persisted as WordPress option |

**Builder selection** (via `PromptBuilderFactory`):
- Beskidy keywords → `MultiLanguageTravelBuilder`
- Travel keywords → `TravelPromptBuilder`
- Everything else → `PromptBuilder`
- Override via `pearblog_prompt_builder_class` filter.

### SEO (`SEO/SEOEngine.php`)

Parses AI-generated content to extract and store:
- Title tag and meta description
- Schema.org structured data
- Open Graph and Twitter Card metadata

### Monetization (`Monetization/MonetizationEngine.php`)

Three monetization layers injected into content:
- **v1 — AdSense**: Ad slots at configurable positions
- **v2 — Affiliate**: Booking.com + Airbnb deep links
- **v3 — SaaS CTA**: Keyword-matched product recommendations

Configurable via admin UI and `pearblog_saas_products` filter.

### Scheduler (`Scheduler/CronManager.php`)

- Registers custom WP-Cron schedules
- Multisite-aware: calls `switch_to_blog()`/`restore_current_blog()` per site
- Triggers `ContentPipeline::run()` on each interval

### Keywords (`Keywords/KeywordCluster.php`)

Immutable value object pairing a pillar keyword with its supporting keywords.
Used by `PromptBuilder` to generate topically-focused content.

### API (`API/AutomationController.php`)

REST API at `pearblog/v1/automation/` with 3 endpoints:

| Endpoint | Method | Description |
|----------|--------|-------------|
| `/create-content` | POST | Queue and generate a new article |
| `/process-content` | POST | Process existing content through pipeline |
| `/status` | GET | Pipeline status and queue info |

Auth: `pearblog_api_key` option or WordPress admin cookie.

### Admin (`Admin/AdminPage.php`)

WordPress admin page providing:
- API key management
- Topic queue CRUD
- SaaS product configuration
- Pipeline status dashboard

### Tenant (`Tenant/`)

| Class | Description |
|-------|-------------|
| `TenantContext` | Runtime context holding domain and profile for a single blog |
| `SiteProfile` | Value object describing content niche and monetization strategy |

---

## WordPress Options

| Option | Description |
|--------|-------------|
| `pearblog_api_key` | REST API authentication key |
| `pearblog_topic_queue` | JSON array of pending topics |
| `pearblog_saas_products` | JSON array of SaaS CTA product definitions |
| `pearblog_pagespeed_api_key` | Google PageSpeed Insights API key (optional) |

---

## Filters

| Filter | Description |
|--------|-------------|
| `pearblog_prompt_builder_class` | Override the prompt builder class selection |
| `pearblog_saas_products` | Modify SaaS product list at runtime |
| `pearblog_airbnb_search_url` | Customize Airbnb affiliate deep link URL |

---

*PearBlog Engine v4 — Namespace `PearBlogEngine`*
