# ⚙️ PearBlog Engine — WordPress MU-Plugin v5.1

> Autonomous AI content production engine for WordPress.

---

## Quick Start

Drop `mu-plugins/pearblog-engine/` into your WordPress `wp-content/mu-plugins/` directory.
The plugin bootstraps automatically — no activation required.

```php
// wp-config.php (optional override)
define('PEARBLOG_OPENAI_API_KEY', 'sk-...');
```

Then add topics in **WP Admin → PearBlog Engine → Queue** and the pipeline runs every hour automatically.

---

## Architecture

```
pearblog-engine.php              # Bootstrap & PSR-4 autoloader
assets/
└── css/admin.css                # Admin panel styles (v5.1)
src/
├── Core/Plugin.php              # Singleton boot — registers all sub-systems
├── Pipeline/ContentPipeline.php # 8-step autonomous content generation
├── AI/
│   ├── AIClient.php             # OpenAI Chat Completions (GPT-4o-mini)
│   ├── ImageGenerator.php       # DALL-E 3 image generation
│   └── ImageAnalyzer.php        # Media library audit + keyword suggestions (v5.1)
├── Content/
│   ├── PromptBuilder.php        # Base prompt builder
│   ├── PromptBuilderFactory.php # Auto-selects builder by niche
│   ├── TravelPromptBuilder.php  # Travel content with mandatory sections
│   ├── BeskidyPromptBuilder.php # Beskidy-specific builder
│   ├── MultiLanguageTravelBuilder.php # PL/EN/DE localization
│   ├── ContentValidator.php     # Validates structure & quality
│   └── TopicQueue.php           # FIFO queue (WordPress option)
├── SEO/
│   ├── SEOEngine.php            # Meta tags & Schema.org injection
│   └── ProgrammaticSEO.php      # Bulk audit, Open Graph, keyword density (v5.1)
├── Monetization/
│   └── MonetizationEngine.php   # AdSense + Affiliate + SaaS CTA
├── Scheduler/CronManager.php    # WP-Cron hook registration (multisite-safe)
├── Keywords/KeywordCluster.php  # Keyword grouping value object
├── API/AutomationController.php # REST API for external automation
├── Admin/
│   ├── AdminPage.php            # Top-level WP admin menu (6 tabs, v5.1)
│   └── DashboardWidget.php      # WP Dashboard pipeline stats widget (v5.1)
└── Tenant/
    ├── TenantContext.php         # Runtime context (domain + profile)
    └── SiteProfile.php           # Value object: niche + strategy
```

---

## Pipeline — 8 Steps

Each call to `ContentPipeline::run()` processes exactly one article (next topic from the queue):

| Step | Action | Class |
|------|--------|-------|
| 1 | Pop topic from queue | `TopicQueue::pop()` |
| 2 | Build prompt | `PromptBuilderFactory::create()` + `build()` |
| 3 | Generate content | `AIClient::generate()` (GPT-4o-mini) |
| 4 | Create draft post | `wp_insert_post()` |
| 5 | Apply SEO metadata | `SEOEngine::apply()` |
| 6 | Inject monetization | `MonetizationEngine::apply()` |
| 6a | Generate featured image | `ImageGenerator::generate_and_attach()` (DALL-E 3) |
| 6b | Auto-generate meta desc fallback | `ProgrammaticSEO::generate_meta_description()` |
| 7 | Publish post | `wp_update_post()` with `post_status=publish` |

Cost: **~$0.08 / article** · Time: **~55 seconds**

---

## Modules

### Core (`Core/Plugin.php`)

Singleton that boots all sub-systems exactly once via `Plugin::boot()`.
Registers: `CronManager`, `AdminPage`, `DashboardWidget`, `ProgrammaticSEO`, and REST routes.

### AI (`AI/`)

| Class | Description |
|-------|-------------|
| `AIClient` | Thin wrapper around OpenAI Chat Completions API |
| `ImageGenerator` | DALL-E 3 image generation with 4 visual styles (photorealistic, illustration, artistic, minimal). Uploads to WordPress media library and sets featured image. |
| `ImageAnalyzer` | **v5.1** — Media library audit: missing alt texts, posts without featured images, AI-generated image tracking, oversized image detection, keyword extraction for generation suggestions. Shared `STOP_WORDS` constant (EN + PL). |

### Content (`Content/`)

| Class | Description |
|-------|-------------|
| `PromptBuilder` | Base prompt builder with SEO and monetization rules |
| `PromptBuilderFactory` | Auto-selects builder based on industry keywords |
| `TravelPromptBuilder` | Travel content with mandatory sections (extends PromptBuilder) |
| `BeskidyPromptBuilder` | Beskidy-specific with weather and day planning (extends TravelPromptBuilder) |
| `MultiLanguageTravelBuilder` | PL/EN/DE localization (extends BeskidyPromptBuilder) |
| `ContentValidator` | Validates structure and quality in 3 modes (generic/travel/beskidy) |
| `TopicQueue` | FIFO topic queue persisted as WordPress option per site |

**Builder selection** (via `PromptBuilderFactory`):
- Beskidy keywords → `MultiLanguageTravelBuilder`
- Travel keywords → `TravelPromptBuilder`
- Everything else → `PromptBuilder`
- Override via `pearblog_prompt_builder_class` filter.

### SEO (`SEO/`)

| Class | Description |
|-------|-------------|
| `SEOEngine` | Parses AI content, extracts title/meta/Schema.org, stores post meta |
| `ProgrammaticSEO` | **v5.1** — Schema.org JSON-LD (Article, BreadcrumbList, FAQPage), Open Graph, Twitter Cards, keyword density analysis, bulk SEO audit, internal link suggestions, meta description auto-generator |

`ProgrammaticSEO::register()` hooks into `wp_head` for automated tag output. `ProgrammaticSEO::bulk_audit($n)` returns:
```php
[
  'posts_audited' => int,
  'issues_found'  => int,
  'issues'        => [ $post_id => ['title' => string, 'issues' => string[]] ],
]
```

### Monetization (`Monetization/MonetizationEngine.php`)

Three monetization layers injected into content:
- **v1 — AdSense**: Ad slots at configurable positions
- **v2 — Affiliate**: Booking.com + Airbnb deep links
- **v3 — SaaS CTA**: Keyword-matched product recommendations

`pearblog_saas_products` option stores a **JSON string** — always `json_decode()` on read.

Configurable via WP Admin → PearBlog Engine → Monetization tab, or `pearblog_saas_products` filter.

### Scheduler (`Scheduler/CronManager.php`)

- Registers custom WP-Cron schedules
- **Multisite-safe**: calls `switch_to_blog()`/`restore_current_blog()` in `try/finally` per site
- Triggers `ContentPipeline::run()` on each interval

### Keywords (`Keywords/KeywordCluster.php`)

Immutable value object pairing a pillar keyword with its supporting keywords.
Used by `PromptBuilder` to generate topically-focused content.

### API (`API/AutomationController.php`)

REST API at `pearblog/v1/automation/` with 3 endpoints:

| Endpoint | Method | Auth | Description |
|----------|--------|------|-------------|
| `/create-content` | POST | API key | Queue and generate a new article |
| `/process-content` | POST | API key | Process existing content through pipeline |
| `/status` | GET | API key | Pipeline status and queue info |

Auth: `pearblog_api_key` option (set in General tab) or WordPress admin cookie.

### Admin (`Admin/AdminPage.php`) — v5.1

Top-level **PearBlog Engine** menu item (position 25, custom icon) with 6 tabbed sections:

| Tab | Content |
|-----|---------|
| **General** | OpenAI API key, Automation API key, niche, tone, language, publish rate |
| **AI Images** | DALL-E 3 toggle, image style, stats, batch generation, missing alt-text fix |
| **SEO** | Bulk audit results with issue tags, auto-fix meta descriptions button |
| **Monetization** | AdSense ID, Booking.com affiliate ID, SaaS products JSON |
| **Email** | ESP provider (Mailchimp/ConvertKit), API keys, list/form IDs |
| **Queue** | Topic queue CRUD (view, add, clear) + site profile summary |

Admin CSS: `assets/css/admin.css` (loaded via `PEARBLOG_ENGINE_URL`).  
Tab state persisted to URL via `history.replaceState` (`?tab=<name>`).

### Dashboard Widget (`Admin/DashboardWidget.php`) — v5.1

WordPress Dashboard widget (right column) showing live pipeline stats:
- Queue size
- Posts published today
- Total AI-generated posts
- Images missing alt text
- Last pipeline run timestamp

### Tenant (`Tenant/`)

| Class | Description |
|-------|-------------|
| `TenantContext` | Runtime context holding domain and profile for a single blog |
| `SiteProfile` | Value object describing content niche and monetization strategy |

---

## WordPress Options

| Option | Type | Description |
|--------|------|-------------|
| `pearblog_openai_api_key` | string | OpenAI API key (secret) |
| `pearblog_api_key` | string | REST API authentication key |
| `pearblog_topic_queue_{site_id}` | JSON | FIFO topic queue per site |
| `pearblog_saas_products` | JSON string | SaaS CTA product definitions array |
| `pearblog_adsense_publisher_id` | string | Google AdSense publisher ID |
| `pearblog_booking_affiliate_id` | string | Booking.com affiliate/partner ID |
| `pearblog_industry` | string | Site niche / industry keyword |
| `pearblog_tone` | string | Writing tone (neutral/professional/conversational/…) |
| `pearblog_monetization` | string | Active monetization strategy (adsense/affiliate/saas) |
| `pearblog_publish_rate` | int | Articles per pipeline cycle |
| `pearblog_language` | string | Content language ISO 639-1 (en/pl/de) |
| `pearblog_enable_image_generation` | bool | DALL-E 3 image generation toggle |
| `pearblog_image_style` | string | DALL-E 3 style (photorealistic/illustration/artistic/minimal) |
| `pearblog_esp_provider` | string | Email provider (none/mailchimp/convertkit) |
| `pearblog_mailchimp_api_key` | string | Mailchimp API key |
| `pearblog_mailchimp_list_id` | string | Mailchimp list/audience ID |
| `pearblog_convertkit_api_key` | string | ConvertKit API key |
| `pearblog_convertkit_form_id` | string | ConvertKit form ID |
| `pearblog_pagespeed_api_key` | string | Google PageSpeed Insights key (optional) |

---

## Filters

| Filter | Args | Description |
|--------|------|-------------|
| `pearblog_prompt_builder_class` | `string $class, SiteProfile $profile` | Override the prompt builder class |
| `pearblog_saas_products` | `array $products` | Modify SaaS product list at runtime |
| `pearblog_airbnb_search_url` | `string $url, string $location` | Customize Airbnb affiliate deep link URL |
| `pearblog_pipeline_started` | `string $topic, TenantContext $context` | Action: pipeline started |
| `pearblog_pipeline_completed` | `int $post_id, string $topic, TenantContext $context` | Action: pipeline completed |
| `pearblog_site_config` | `array $config` | Filter all site configuration values |

---

## Security Notes

- API key validated via `pearblog_lead_permission_check()` with rate limiting (5 submissions/IP/hour)
- SQL queries use `$wpdb->prepare()` — no string interpolation in queries
- All admin form fields use `settings_fields()` + `register_setting()` with typed `sanitize_callback`
- `.env` and `.env.*` files are gitignored since v4.3

---

*PearBlog Engine v5.1 — Namespace `PearBlogEngine`*
