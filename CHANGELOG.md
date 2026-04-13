# Changelog

All notable changes to PearBlog Engine are documented in this file.

## [7.7.0] — 2026-04-13

### Added — v7.7 Developer Experience & Extensibility

#### Plugin Hooks Reference

- **`DEVELOPER-HOOKS.md`** — comprehensive reference for all 30 action/filter hooks (17 actions + 13 filters) exposed by PearBlog Engine; each entry includes the full signature, parameter descriptions, source file, and a real-world usage example; structured into thematic sections: Pipeline Lifecycle, Background Processing, Content Operations, SEO & Publishing, Social & Distribution, Monitoring & SLA, A/B Testing, CDN & Cache, Admin Trigger, CLI / Autopilot, Prompt Generation, Builder Selection, Monetisation, SEO & Internal Links, Monitoring Thresholds, Image Generation.

#### Developer CLI Scaffolding Tools

- **`wp pearblog scaffold prompt-builder <ClassName> [--industry=<industry>] [--dir=<dir>]`** — generates a fully-stubbed `PromptBuilder` subclass with the correct namespace, `build()` method, monetisation helper call, and both the industry-specific and `pearblog_prompt` filter hooks wired up; accepts optional `--industry` label (defaults to class name with "PromptBuilder" stripped) and `--dir` for output directory.
- **`wp pearblog scaffold provider <ClassName> [--dir=<dir>]`** — generates a fully-stubbed `AIProviderInterface` implementation with all required metadata methods (`get_provider_slug`, `get_provider_label`, `get_models`, `get_default_model`, `requires_option`) and a `complete()` stub that throws `\RuntimeException` until implemented.
- Both subcommands guard against overwriting an existing file and print clear "Next steps" instructions after generation.

#### `wp pearblog audit` Command

- **`wp pearblog audit list [--limit=<n>] [--level=<level>] [--event=<event>]`** — list recent audit events in reverse-chronological order with optional severity and event-type filters.
- **`wp pearblog audit clear`** — erase all stored audit events.
- **`wp pearblog audit stats`** — print a summary: total events, per-level counts, and the top-10 event types.

#### Event-Sourced Pipeline Audit Log

- **`PipelineAuditLog`** (`src/Pipeline/PipelineAuditLog.php`) — append-only ring-buffer (max 500 entries) stored in `pearblog_audit_log` WP option; automatically hooks into 16 pipeline actions (`pearblog_pipeline_started`, `pearblog_pipeline_completed`, `pearblog_pipeline_duplicate_skipped`, `pearblog_pipeline_cron_error`, `pearblog_seo_applied`, `pearblog_quality_scored`, `pearblog_content_refreshed`, `pearblog_translation_created`, `pearblog_social_published`, `pearblog_cdn_offloaded`, `pearblog_bg_job_completed`, `pearblog_bg_job_failed`, `pearblog_sla_breached`, `pearblog_abtest_winner_promoted`) and records structured event entries with `id`, `timestamp`, `event`, `level`, and `context` fields; three severity levels: `info`, `warning`, `error`; registered in `Plugin::boot()`.
- **REST endpoint `GET /pearblog/v1/audit`** — returns filtered events; query params: `limit` (1–500, default 50), `level` (info/warning/error), `event` (slug); authentication via `manage_options` capability or Bearer API token.
- **REST endpoint `POST /pearblog/v1/audit/append`** — admin-only endpoint to manually append a custom event; body: `event` (required), `level` (default info), `context` (object).
- Bootstrap test stubs added for `current_user_can()` (global-variable-controlled, defaults to `false`) and `WP_REST_Server` class constants.

### Tests

- **38 new PHPUnit tests** in `PipelineAuditLogTest.php`:
  - Append / count / clear (7 tests)
  - `get_events` filtering and ordering (6 tests)
  - Ring-buffer enforcement (2 tests)
  - All 14 action callbacks (14 tests)
  - REST permission callbacks (2 tests)
  - REST `GET /audit` endpoint (3 tests)
  - REST `POST /audit/append` endpoint (4 tests — including 400 validation)
- **640 tests / 1196 assertions** — all passing.

---

## [7.6.0] — 2026-04-13

### Added — v7.6 Performance & Infrastructure

#### Object Cache Integration

- **`ObjectCacheAdapter`** (`src/Cache/ObjectCacheAdapter.php`) — wraps the native WordPress Object Cache API (`wp_cache_get/set/delete`) so all PearBlog caching automatically benefits from Redis, Memcached, or APCu when a persistent object-cache drop-in is installed; falls back to an in-memory store (eliminating SQL transient reads) on vanilla installs; group-scoped `flush_group()` uses `wp_cache_flush_group()` (WP 6.1+) or falls back to `wp_cache_flush()`; all entries stored in the configurable `pearblog` cache group with `wp_cache_add_global_groups()` support for multisite; typed helpers: `set/get_ai_content`, `set/get_seo_meta`, `set/get_link_candidates`, `set/get_duplicate_hash`; options: `pearblog_object_cache_group`, `pearblog_object_cache_ttl_ai/seo/links`.
- Bootstrap test stubs added for the full `wp_cache_*` API and `wp_using_ext_object_cache()`.

#### Async Pipeline (Background Processing)

- **`BackgroundProcessor`** (`src/Pipeline/BackgroundProcessor.php`) — persistent job queue (`pearblog_bg_queue` WP option) with one-off WP-Cron events (`pearblog_bg_process`) so pipeline runs never block HTTP requests; `dispatch(topic, tenant_id)` enqueues a job and schedules a cron event 5 seconds later; `handle_batch()` processes up to `MAX_BATCH_SIZE` (default 5) jobs per cron invocation; jobs decouple via `pearblog_bg_run_pipeline` action (ContentPipeline hooks in via Plugin::boot()); exponential back-off retry on failure (2^attempts minutes); `pearblog_bg_job_completed` / `pearblog_bg_job_failed` action hooks; `cancel(id)` and `clear_queue()` management methods; options: `pearblog_bg_enabled`, `pearblog_bg_max_batch_size`, `pearblog_bg_max_attempts`.
- Bootstrap test stubs added for `wp_schedule_single_event`.

#### CDN Image Auto-Offload

- **`CdnManager`** (`src/Cache/CdnManager.php`) — uploads AI-generated WP attachment images to a CDN and rewrites `wp_get_attachment_url()` responses transparently via a `wp_get_attachment_url` filter; two providers: **BunnyCDN** Storage + pull zone (default) and **Cloudflare Images** API; stores CDN URL in `_pearblog_cdn_url` post meta + provider in `_pearblog_cdn_provider`; `offload_attachment(id)` is idempotent; `remove_from_cdn(id)` deletes from the CDN and clears meta; optional local file deletion after offload (`pearblog_cdn_delete_local`); `pearblog_cdn_offloaded` action hook; options: `pearblog_cdn_enabled`, `pearblog_cdn_provider`, `pearblog_cdn_bunny_api_key/zone_name/region/pull_zone_url`, `pearblog_cdn_cf_account_id/api_token/delivery_url`.
- Bootstrap test stubs added for `wp_remote_request`, `get_attached_file`, `delete_post_meta`.

### Tests

- **62 new PHPUnit tests** across 3 new test classes:
  - `ObjectCacheAdapterTest` (23 tests) — get/set/delete, flush_group, all typed helpers, is_persistent, register, cache_key
  - `BackgroundProcessorTest` (22 tests) — dispatch, cancel, queue persistence, handle_batch, batch size, retry/failure logic, action hooks
  - `CdnManagerTest` (27 tests) — enabled guard, providers, offload success/failure, filter_attachment_url, remove_from_cdn, meta keys
- **588 tests / 1096 assertions** — all passing.

---

## [7.5.0] — 2026-04-13

### Added — v7.5 Content Automation 2.0

#### SERP Scraper

- **`SerpScraper`** (`src/Content/SerpScraper.php`) — fetches top organic search results from configurable third-party SERP APIs; two providers supported out of the box: **Value SERP** (default) and **Serper.dev**; results cached via WP transients (`pearblog_serp_cache_ttl`, default 24 h); options: `pearblog_serp_provider`, `pearblog_serp_api_key`, `pearblog_serp_results_count`, `pearblog_serp_country`, `pearblog_serp_language`; `fetch_titles()` convenience method integrates directly with `CompetitiveGapEngine::set_competitor_topics()`.

#### Auto-Keyword Clustering

- **`KeywordClusterEngine`** (`src/Keywords/KeywordClusterEngine.php`) — groups GA4 organic search terms into `KeywordCluster` value objects using a greedy IDF-based clustering algorithm with configurable Jaccard similarity threshold; pulls raw terms from `GA4Client::run_report()` (dimension: `searchTerm`); persists cluster snapshots to `pearblog_kce_clusters` WP option; weekly cron (`pearblog_keyword_cluster_refresh`) keeps clusters fresh; options: `pearblog_kce_similarity_thresh` (0.25), `pearblog_kce_min_cluster_size` (2), `pearblog_kce_max_clusters` (20), `pearblog_kce_ga4_days` (90).

#### Multilingual Content Generation

- **`MultilingualManager`** (`src/Content/MultilingualManager.php`) — AI-powered translation of existing posts into multiple target languages; configurable prompt template with `{language}` / `{source}` placeholders; creates translated draft posts and stores `_pearblog_ml_source_post_id` + `_pearblog_ml_language` meta; native WPML integration via `wpml_set_element_language_details` (auto-detected); native Polylang integration via `pll_set_post_language` + `pll_save_post_translations` (auto-detected); fires `pearblog_translation_created` action on success; options: `pearblog_ml_target_languages`, `pearblog_ml_post_status` (draft), `pearblog_ml_prompt_template`, `pearblog_ml_enabled`.

### Tests

- **61 new PHPUnit tests** across 3 new test classes:
  - `SerpScraperTest` (35 tests) — configuration, caching, both provider parsers, edge cases
  - `KeywordClusterEngineTest` (18 tests) — tokenisation, Jaccard similarity, cluster grouping, min/max options, persistence
  - `MultilingualManagerTest` (18 tests) — enabled guard, language management, AI prompt injection, post creation, meta storage, disabled guard
- **526 tests / 1019 assertions** — all passing.

---

## [7.4.0] — 2026-04-12

### Added — v7.4 Competitive Intelligence + Analytics + GraphQL

#### v7.2 Completion — Competitive Gap Analysis

- **`CompetitiveGapEngine`** (`src/Content/CompetitiveGapEngine.php`) — compares a configurable list of competitor topics against the published post corpus using Jaccard similarity; returns uncovered ("gap") topics sorted by lowest similarity first; injects top N gaps into AI prompts via `enrich_prompt()`; options: `pearblog_gap_competitor_topics`, `pearblog_gap_max_inject` (default 3), `pearblog_gap_similarity_thresh` (default 0.5).

#### v7.1 Completion — Advanced Analytics Dashboard

- **`GA4Client`** (`src/Analytics/GA4Client.php`) — Google Analytics 4 Data API v1beta client; authenticates via service-account JWT (RS256 signed, stored as `pearblog_ga4_credentials`); fetches `screenPageViews` per post path, top-N posts, and site-wide totals; results cached via WP transients (`pearblog_ga4_cache_ttl`, default 3600 s).
- **`AnalyticsDashboard`** (`src/Analytics/AnalyticsDashboard.php`) — daily cron (`pearblog_analytics_sync`) syncs GA4 views to `_pearblog_ga4_views_30d` / `_pearblog_ga4_views_7d` post meta; `get_top_performing_posts()` blends quality score and page views into a `performance_score`; `get_summary()` returns site-wide stats for the admin Analytics tab.

#### v7.1 Completion — GraphQL API

- **`GraphQLController`** (`src/API/GraphQLController.php`) — dual integration: (a) registers types and fields with WPGraphQL if active (`PearBlogStats`, `PearBlogPost`, `PearBlogHealth`; root fields `pearBlogStats`, `pearBlogTopPosts`, `pearBlogHealth`, `pearBlogQueue`); (b) always registers a standalone REST endpoint at `GET|POST /pearblog/v1/graphql` with a built-in query resolver for the same four queries; auth via bearer token or `manage_options`.

### New `src/Analytics/` module

First analytics module in the codebase. Two classes: `GA4Client` + `AnalyticsDashboard`.

### Tests

- **71 new PHPUnit tests** across 4 new test classes:
  - `CompetitiveGapEngineTest` (21 tests) — tokenisation, Jaccard similarity, gap analysis, threshold, prompt enrichment
  - `GA4ClientTest` (14 tests) — configuration checks, response parsing, caching
  - `AnalyticsDashboardTest` (12 tests) — summary, top posts, sync guard, meta key constants
  - `GraphQLControllerTest` (24 tests) — all resolvers, request handling, permission checks
- **465 tests / 932 assertions** — all passing.

---

## [7.3.0] — 2026-04-12

### Added — v7.3 Enterprise Features

#### Advanced Prompt Engineering (v7.2 completion)

- **`FewShotEngine`** (`src/Content/FewShotEngine.php`) — pulls top-scoring published articles (score ≥ `pearblog_fewshot_min_score`, default 70) and injects their excerpts into the AI prompt as style examples; configurable via `pearblog_fewshot_enabled`, `pearblog_fewshot_max_posts`, `pearblog_fewshot_excerpt_len`.
- **`PersonaBuilder`** (`src/Content/PersonaBuilder.php`) — manages named author personas (name, bio, writing style, tone, vocabulary preferences/avoidances); the active persona is appended to every AI prompt; CRUD API: `save_persona()`, `get_persona()`, `delete_persona()`, `set_active()`, `enrich_prompt()`.

#### White-Label Manager

- **`WhiteLabelManager`** (`src/Admin/WhiteLabelManager.php`) — full white-label branding override: custom brand name, admin menu label, logo URL, accent hex colour, support URL, and optional admin footer suppression; all settings registered under the `pearblog_branding` settings group; accent CSS injected via `admin_head`.

#### Advanced Permissions & Audit Log

- **`PermissionManager`** (`src/API/PermissionManager.php`) — role-based access control for pipeline trigger, pause, and settings actions; configurable per-action role lists stored as JSON options; ring-buffer audit log (500 entries max) with actor, action, context, success flag, and timestamp; `log()`, `get_audit_log()`, `clear_audit_log()`, `role_can()`, `set_allowed_roles()`.

#### SLA Management

- **`SLAManager`** (`src/Monitoring/SLAManager.php`) — configurable per-site SLA targets for uptime %, pipeline success %, API response time (ms), and cost per article (cents); hourly evaluation cron (`pearblog_sla_check`) fires `pearblog_sla_breached` action on any breach; monthly report cron (`pearblog_sla_report`) stores a 12-month history and optionally e-mails the report to `pearblog_sla_report_email`; `evaluate()`, `generate_monthly_report()`, `get_history()`, `set_targets()`.

### Tests

- **71 new PHPUnit tests** across 5 new test classes:
  - `FewShotEngineTest` (11 tests)
  - `PersonaBuilderTest` (19 tests)
  - `WhiteLabelManagerTest` (21 tests)
  - `PermissionManagerTest` (17 tests)
  - `SLAManagerTest` (17 tests) — including e-mail dispatch verification and history persistence.
- **394 tests / 827 assertions** — all passing.

---

## [7.2.1] — 2026-04-12

### Added — v7.2 Multi-Model Support

- **`AIProviderInterface`** (`src/AI/AIProviderInterface.php`) — provider contract with `complete()`, `get_slug()`, `get_label()`, `get_api_key_option()`, `get_models()`, `get_default_model()`.
- **`OpenAIProvider`** (`src/AI/OpenAIProvider.php`) — extracted from AIClient; implements the interface for gpt-4o, gpt-4o-mini, gpt-4-turbo, gpt-3.5-turbo.
- **`AnthropicProvider`** (`src/AI/AnthropicProvider.php`) — Claude 3.5 Sonnet + Claude 3 Haiku via `api.anthropic.com/v1/messages`; treats HTTP 529 (overloaded) as a rate-limit for retry.
- **`GeminiProvider`** (`src/AI/GeminiProvider.php`) — Gemini 1.5 Pro + Flash via `generativelanguage.googleapis.com/v1beta/models/{model}:generateContent`.
- **`AIProviderFactory`** (`src/AI/AIProviderFactory.php`) — `make('openai'|'anthropic'|'gemini')`, `get_all_providers()`, `get_all_models()`, `get_active_provider_models()`, `get_active_provider_default_model()`, `get_active_api_key_option()`. Active provider stored in `pearblog_ai_provider` option.
- **`RateLimitException`** (`src/AI/RateLimitException.php`) — extracted to its own file so providers can throw it without importing AIClient.
- **Updated `AIClient`** — `do_request()` now delegates to a provider via `AIProviderFactory::make()`; constructor accepts `?AIProviderInterface $provider` override for testing; `get_model()` / `get_available_models()` are now provider-aware; `estimate_cost_cents()` searches all providers' model lists.
- **Admin UI** — new **AI Provider** dropdown (`pearblog_ai_provider`) + Anthropic/Gemini API key rows that show/hide based on selection; inline JS filters the Model dropdown per provider. Settings registered: `pearblog_ai_provider`, `pearblog_anthropic_api_key`, `pearblog_gemini_api_key`.
- **38 new PHPUnit tests** in `AIProviderFactoryTest`; updated `AIClientTest` with provider-injection tests and stub provider.
- **323 tests / 721 assertions** — all passing.



### Added — v7.2 GPT-4o / Multi-Model Support

- **Configurable AI model** — `pearblog_ai_model` option selects the active OpenAI model; supported values: `gpt-4o`, `gpt-4o-mini` (default), `gpt-4-turbo`, `gpt-3.5-turbo`.
- **`AIClient::MODELS` constant** — full metadata map per model: human label, max tokens, input/output cost rates (per 1k tokens in USD cents).
- **`AIClient::get_model()`** — reads `pearblog_ai_model` option; falls back to `gpt-4o-mini` if unset or invalid slug.
- **`AIClient::get_available_models()`** — returns the full models map; used by the admin dropdown.
- **`AIClient::estimate_cost_cents()`** — calculates blended cost estimate for a given token count and model (40 % input / 60 % output ratio).
- **Accurate per-model billing** — `do_request()` now splits `prompt_tokens` and `completion_tokens` and applies the correct input/output rate per model instead of the former flat average.
- **Admin model selector** — new row in Admin → General → AI Settings lets site admins change the model from the UI; setting registered via `register_setting()`.
- **13 new PHPUnit tests** in `AIClientTest` covering model selection, fallback, available-model list, cost estimation accuracy, and constructor injection.



### Added — v7.1 A/B Testing Framework

- **ABTestEngine** (`src/Testing/ABTestEngine.php`) — split-test two prompt variants (modifier A vs modifier B) for the same topic; winner elected automatically after 7 days by highest average quality score.
- **Balanced variant selection** — round-robin assignment keeps runs per variant equal; ties default to variant A.
- **Automatic hook integration** — registers on `pearblog_prompt` filter to append the variant's modifier, and on `pearblog_pipeline_completed` to record quality scores; no changes required to `ContentPipeline`.
- **Daily auto-promotion cron** — `pearblog_abtest_promote` hook calls `promote_mature_tests()` each day; requires at least 2 articles per variant before electing a winner.
- **`wp pearblog abtest` CLI commands** — `create`, `list`, `status`, `promote`, `delete` subcommands for full command-line management.
- **34 new PHPUnit tests** in `tests/php/Unit/ABTestEngineTest.php` covering CRUD, variant selection, score recording, promotion, hook callbacks, and edge cases.

## [6.0.2] — 2026-04-12

### Added — v7.1 Monitoring Dashboard UI

- **Alert history table** — Monitoring tab now renders the last 50 alerts from `AlertManager`'s ring buffer, colour-coded by severity level (Info / Warning / Error / Critical) with a priority badge (P0–P3).
- **Alert level filter** — Quick-filter links above the history table let you narrow to a single severity level without leaving the page.
- **Active silences display** — When one or more alert silences are active, a table shows the muted pattern, affected level, and expiry time.
- **Reset Performance Metrics action** — One-click button (with confirmation dialog) to wipe all performance metric records stored in `pearblog_perf_metrics` and `pearblog_perf_daily`.
- **Clear Alert History action** — One-click button to clear `pearblog_alert_history`.
- **Export CSV download** — Form button that streams all pipeline run records as a CSV file (`pearblog-metrics-YYYY-MM-DD.csv`). Each row includes timestamp, type, post ID, topic, duration, memory, cost, and DB queries.
- **`PerformanceDashboard::get_csv_rows()`** — New public method returning the header row + data rows as a plain PHP array, making the CSV generation independently testable.
- **4 new PHPUnit tests** for `get_csv_rows()` in `PerformanceDashboardTest`.

## [6.0.0] — 2026-04-05

### Added — Sprint 1: Critical Stability

- **AIClient: Exponential backoff & retry** — Retries up to 3× on HTTP 429 rate-limit responses with full-jitter exponential backoff (AI/AIClient.php)
- **AIClient: Circuit breaker** — After 5 consecutive failures the client opens the circuit for 5 minutes, preventing cascading failures (AI/AIClient.php)
- **AIClient: API cost tracking** — Accumulates estimated per-token spend in USD cents in `pearblog_ai_cost_cents` option (AI/AIClient.php)
- **AlertManager** — Sends notifications to Slack, Discord, and/or email on pipeline errors, circuit-breaker events, and article publications (Monitoring/AlertManager.php)
- **HealthController** — `GET /pearblog/v1/health` endpoint exposing API key status, OpenAI connectivity, circuit-breaker state, queue size, last run, and AI cost (Monitoring/HealthController.php)
- **CronManager: alert hook** — Fires `pearblog_pipeline_cron_error` action on pipeline failure so AlertManager can dispatch notifications (Scheduler/CronManager.php)

### Added — Sprint 2: SEO

- **InternalLinker** — Keyword-cluster-based internal link injection: scans content for titles/keywords of published posts and inserts up to 5 contextual links (SEO/InternalLinker.php)
- **InternalLinker: backfill** — `backfill()` method and `wp pearblog links backfill` CLI command to add internal links to all existing posts (SEO/InternalLinker.php)
- **SchemaManager** — Auto-generates JSON-LD Schema.org blocks for every single post: Article, FAQPage (from H3 Q&A pairs), BreadcrumbList (SEO/SchemaManager.php)

### Added — Sprint 3: Content Quality

- **QualityScorer** — Scores published posts 0–100 on readability (Flesch), keyword density, heading structure, and word count; stores as post meta (Content/QualityScorer.php)
- **ContentRefreshEngine** — Weekly cron job finds posts older than 90 days and AI-refreshes them; prioritises declining-traffic posts (Content/ContentRefreshEngine.php)
- **ContentPipeline: duplicate check** — Before creating any draft, checks cosine similarity against all published posts; skips if similarity ≥ 80% (Pipeline/ContentPipeline.php)
- **ContentPipeline: quality scoring** — Scores every newly published article immediately after publication (Pipeline/ContentPipeline.php)
- **ContentPipeline: internal linking** — Applies InternalLinker after monetisation step (Pipeline/ContentPipeline.php)
- **ContentPipeline: duplicate indexing** — Indexes TF vector of new posts for future duplicate checks (Pipeline/ContentPipeline.php)

### Added — Sprint 4: Content Integrity

- **DuplicateDetector** — TF-IDF cosine-similarity duplicate detection with configurable threshold (Content/DuplicateDetector.php)
- **ContentCalendar** — Tools → Content Calendar admin page: schedule topics for specific dates; daily cron auto-pushes them to the queue (Admin/ContentCalendar.php)
- **ContentCalendar REST API** — `GET/POST /pearblog/v1/calendar` and `DELETE /pearblog/v1/calendar/<date>` endpoints (Admin/ContentCalendar.php)

### Added — Sprint 5: Distribution

- **EmailDigest** — Weekly newsletter via Mailchimp Campaigns API, ConvertKit Broadcasts API, or wp_mail fallback (Email/EmailDigest.php)
- **SocialPublisher** — Auto-posts to Twitter/X (OAuth 1.0a), Facebook Pages (Graph API), and LinkedIn (UGC Posts API) after each publication (Social/SocialPublisher.php)

### Added — Sprint 6: DevOps

- **WP-CLI commands** — Full `wp pearblog` command group: `generate`, `queue list/add/clear`, `stats`, `refresh`, `quality score`, `duplicate check`, `links backfill`, `circuit status/reset` (CLI/PearBlogCommand.php)
- **WebhookManager** — Configurable outbound webhooks for events: `pearblog.article_published`, `pearblog.pipeline_error`, `pearblog.quality_scored`, `pearblog.content_refreshed`; signed with HMAC-SHA256; REST CRUD (Webhook/WebhookManager.php)

### Added — Tests & CI

- **PHPUnit test suite** — 52 tests, 81 assertions covering: AIClient (circuit breaker, cost), TopicQueue (FIFO, isolation), ContentValidator, DuplicateDetector, QualityScorer, SEOEngine, KeywordCluster (tests/php/Unit/)
- **Bootstrap with WP stubs** — Comprehensive WordPress function/class stubs for testing without WordPress (tests/php/bootstrap.php)
- **Composer + phpunit.xml** — PHPUnit 10.x test runner configuration (composer.json, phpunit.xml)
- **pytest tests** — Python test modules for keyword_engine, serp_analyzer, automation_orchestrator (tests/python/)
- **GitHub Actions `test.yml`** — CI workflow running PHPUnit (PHP 8.1/8.2/8.3), pytest (Python 3.10/3.11/3.12), and PHP syntax check on every PR (.github/workflows/test.yml)

### Changed

- **Plugin.php** — Boots all new sub-systems: SchemaManager, ContentRefreshEngine, ContentCalendar, EmailDigest, SocialPublisher, WebhookManager, WP-CLI commands, monitoring hooks
- **AdminPage.php** — Added settings for: monitoring (Slack/Discord webhook, alert email), duplicate check toggle, social media channels and credentials, email digest address
- **Plugin version** — 6.0.0


## [5.3.0] — 2026-04-05

### Fixed — Missing Affiliate Settings (Not Saveable via Admin UI)
- **`pearblog_booking_api_key`** — read by `pearblog_fetch_booking_offers()` in `affiliate-api.php` but never registered with `register_setting()` and never exposed in the admin form. Values set programmatically via `update_option()` would be silently discarded on the next Settings save.
- **`pearblog_airbnb_api_key`** — same issue as above for Airbnb.
- **`pearblog_airbnb_affiliate_id`** — same issue; required to build Airbnb deep-link URLs.

### Added — Affiliate Settings Admin UI Fields
- Three new rows added to the Monetization tab: **Booking.com API Key**, **Airbnb Affiliate ID**, **Airbnb API Key**. All three use `password` or `text` inputs as appropriate and include inline description text. All render as password inputs where applicable to prevent shoulder-surfing.



### Fixed — Fatal PHP Errors (Duplicate Function Definitions)
- **`pearblog_extract_location_from_content()`** — defined in both `affiliate-api.php` (old, keyword-list) and `monetization.php` (new, regex + taxonomy). Removed the old definition from `affiliate-api.php`; the richer version in `monetization.php` is now the sole implementation.
- **`pearblog_affiliate_box()`** — defined in both `affiliate-api.php` (stub, no offer fetching) and `monetization.php` (full, fetches Booking/Airbnb/SaaS offers). Removed the stub from `affiliate-api.php`.
- **`pb_get_headline_variant()`** — defined in both `ai-optimizer.php` (v4, DB-table-based) and `ab-testing-metabox.php` (v5, post-meta-based with winner detection). Removed the old definition from `ai-optimizer.php`.
- **`pb_track_ab_impression()`** — same split as above; removed DB-based version from `ai-optimizer.php`.
- **`pb_track_ab_click()`** — same split as above; removed DB-based version from `ai-optimizer.php`.
- **`pb_get_ab_test_results()`** — same split as above; removed DB-based version from `ai-optimizer.php`.

### Fixed — Argument Count Mismatch
- **`pearblog_fetch_booking_offers()`** — previously required `$api_key` and `$affiliate_id` as mandatory positional args. The newer `pearblog_affiliate_box()` in `monetization.php` calls it with only `$location`. Both parameters now default to `''` and auto-fetch from `pearblog_booking_api_key` / `pearblog_booking_affiliate_id` options when omitted.
- **`pearblog_fetch_airbnb_offers()`** — same fix: `$api_key` and `$affiliate_id` now default to `''` and auto-fetch from `pearblog_airbnb_api_key` / `pearblog_airbnb_affiliate_id` options.



### Added — Full Autonomous Mode
- **`pearblog_autonomous_mode` setting** — New admin toggle that enables or disables the fully autonomous WP-Cron pipeline without touching code. Defaults to `true` (enabled) to preserve backwards compatibility.
- **Pipeline Status dashboard** — The admin page now shows the current mode (Autonomous / Manual), next scheduled run time, and current queue count in a summary table.
- **"Run Pipeline Now" button** — Administrators can trigger the content pipeline on-demand from the admin page without waiting for the next cron cycle. The button is disabled when the queue is empty.
- **Manual pipeline action handler** (`handle_run_pipeline`) — registered as `admin_post_pearblog_run_pipeline` with nonce verification and capability check.

### Fixed
- **Email marketing settings not persisting** — `pearblog_esp_provider`, `pearblog_mailchimp_api_key`, `pearblog_mailchimp_list_id`, `pearblog_convertkit_api_key`, and `pearblog_convertkit_form_id` were rendered in the admin UI but never registered with `register_setting()`, so saving them had no effect. All five settings are now properly registered.

### Changed
- **CronManager::maybe_schedule()** — Now reads `pearblog_autonomous_mode`. When the mode is disabled the method unschedules any existing cron event instead of leaving it in place; when enabled it schedules as before.

## [5.1.0] — 2026-04-04

### Added — Frontend Expansion
- **Reading progress bar** — Sticky top indicator fills as user scrolls through articles; wired to `#pb-reading-progress-bar`
- **Dark mode toggle button** — Moon/sun icon in header nav; persists preference to `localStorage`; respects `prefers-color-scheme`
- **Search panel** — Slide-down search form triggered by header search icon; closes on Escape or outside click
- **Sticky header** — Header shrinks on scroll with box shadow; `.pb-nav--sticky` class toggled by JS
- **Google Fonts** — Poppins (display) + Inter (UI) loaded via `wp_enqueue_style` with `display=swap`
- **`page.php`** — Full static page template with hero area, sidebar support, reading progress
- **`search.php`** — Search results template with query context, card grid, and "no results" state
- **`404.php`** — Error page with hero, popular posts, and category browser
- **Admin top-level menu** — PearBlog Engine now appears as a top-level menu item (with icon) instead of under Settings
- **Admin tabbed interface** — Six tabs: General, AI Images, Programmatic SEO, Monetization, Email, Queue
- **Admin CSS** — `mu-plugins/pearblog-engine/assets/css/admin.css` — fully styled admin panel with cards, badges, and status indicators
- **Font CSS variables** — `--pb-font-display` (Poppins) and `--pb-font-ui` (Inter) added to `base.css`

### Added — Programmatic SEO (from v5.0 session)
- **`ProgrammaticSEO.php`** — Schema.org JSON-LD (Article, BreadcrumbList, FAQPage), Open Graph, Twitter Cards, auto meta-description generation, keyword density analysis, bulk SEO audit, internal linking suggestions
- **`ImageAnalyzer.php`** — Media library audit: missing featured images, missing alt texts, AI-generated image tracking, oversized image detection, keyword-based generation suggestions

### Changed
- **`ContentPipeline.php`** — Step 6b: auto-generates meta description fallback after AI content creation
- **`Plugin.php`** — Boots `ProgrammaticSEO::register()` for front-end SEO output
- **`AdminPage.php`** — Migrated from Settings submenu to top-level menu; tabbed navigation UI; admin CSS enqueue; SEO audit + Image Generator sections

## [5.0.0] — 2026-04-04

### Added - Missing Theme Functions
- **`pearblog_extract_location_from_content()`** — Extract location from article title/content using pattern matching for Polish/European travel destinations (monetization.php:344-378)
- **`pearblog_affiliate_box()`** — Render affiliate monetization boxes with fallback support for Booking.com, Airbnb, and SaaS CTAs (monetization.php:380-450)
- **`pearblog_get_saas_cta()`** — Match post content to SaaS products using keyword scoring algorithm (monetization.php:452-500)
- **`pb_get_headline_variant()`** — A/B testing function that serves headline variants and tracks impressions with session-based consistency (ab-testing-metabox.php:197-258)
- **`pb_track_ab_impression()`** and **`pb_track_ab_click()`** — Track A/B test metrics with daily and lifetime aggregates (ab-testing-metabox.php:260-312)
- **`pb_get_ab_test_results()`** — Calculate A/B test results with CTR and automatic winner detection (ab-testing-metabox.php:314-352)
- **`pb_check_ab_test_winner()`** — Auto-declare winner after 100+ impressions with 10% CTR difference threshold (ab-testing-metabox.php:354-387)

### Added - Security Features
- **Rate limiting for lead submissions** — `pearblog_lead_permission_check()` function limits submissions to 5 per IP per hour using WordPress transients (lead-generation.php:121-162)
- **IP-based rate limiting** — Prevents spam and DoS attacks on public REST API endpoints

### Fixed - Critical Security Vulnerabilities
- **SQL injection in behavior-tracking.php** — Fixed table name interpolation using `$wpdb->prepare()` and `esc_like()` for SHOW TABLES query (behavior-tracking.php:32-36)
- **Permission callback on lead endpoint** — Changed from `__return_true` to `pearblog_lead_permission_check` with rate limiting (lead-generation.php:63)

### Changed
- **Theme version** — Updated from 4.0.0 to 5.0.0 for major feature additions
- **A/B testing integration** — Complete implementation with automatic winner detection and post title updates

### Fixed - Theme Functionality
- **Missing function calls** — All 6 undefined function calls in single.php now properly implemented
- **A/B testing meta box** — Now fully functional with result tracking and winner selection

## [5.1.0] — 2026-04-04

### Added — Frontend Expansion
- **Reading progress bar** — Sticky top indicator fills as user scrolls through articles; wired to `#pb-reading-progress-bar`
- **Dark mode toggle button** — Moon/sun icon in header nav; persists preference to `localStorage`; respects `prefers-color-scheme`
- **Search panel** — Slide-down search form triggered by header search icon; closes on Escape or outside click
- **Sticky header** — Header shrinks on scroll with box shadow; `.pb-nav--sticky` class toggled by JS
- **Google Fonts** — Poppins (display) + Inter (UI) loaded via `wp_enqueue_style` with `display=swap`
- **`page.php`** — Full static page template with hero area, sidebar support, reading progress
- **`search.php`** — Search results template with query context, card grid, and "no results" state
- **`404.php`** — Error page with hero, popular posts, and category browser
- **Admin top-level menu** — PearBlog Engine now appears as a top-level menu item (with icon) instead of under Settings
- **Admin tabbed interface** — Six tabs: General, AI Images, Programmatic SEO, Monetization, Email, Queue
- **Admin CSS** — `mu-plugins/pearblog-engine/assets/css/admin.css` — fully styled admin panel with cards, badges, and status indicators
- **Font CSS variables** — `--pb-font-display` (Poppins) and `--pb-font-ui` (Inter) added to `base.css`

### Added — Programmatic SEO
- **`ProgrammaticSEO.php`** — Schema.org JSON-LD (Article, BreadcrumbList, FAQPage), Open Graph, Twitter Cards, auto meta-description generation, keyword density analysis, bulk SEO audit, internal linking suggestions
- **`ImageAnalyzer.php`** — Media library audit: missing featured images, missing alt texts, AI-generated image tracking, oversized image detection, keyword-based generation suggestions

### Changed
- **`ContentPipeline.php`** — Step 6b: auto-generates meta description fallback after AI content creation
- **`Plugin.php`** — Boots `ProgrammaticSEO::register()` for front-end SEO output
- **`AdminPage.php`** — Migrated from Settings submenu to top-level menu; tabbed navigation UI; admin CSS enqueue; SEO audit + Image Generator sections

## [4.3.0] — 2026-04-04

### Added
- `mu-plugins/pearblog-engine/README.md` — plugin architecture documentation covering all 11 modules, WordPress options, filters, and REST API.
- `scripts/README.md` — Python automation suite documentation with module details, environment variables, and usage examples.

### Fixed
- **Workflow:** `run-roadmap.yml` git rebase now falls back to merge on conflict instead of silently aborting.

### Changed
- `.gitignore` — added `.env` and `.env.*` patterns for secret protection.
- `logs/.gitkeep` replaced with `logs/.gitignore` (properly ignores log files while keeping directory).
- `DOCUMENTATION-INDEX.md` updated to reference new documentation and bumped to v4.3.

## [4.2.0] — 2026-04-04

### Fixed
- **CRITICAL:** `SEOEngine::apply()` — `compact()` call with `=>` syntax caused PHP parse error, breaking the entire content pipeline. Replaced with array literal.

### Added
- `template-parts/block-tldr.php` — missing template part called from `dynamic-content.php` for TL;DR rendering.

### Removed
- Dead code PHP classes never instantiated anywhere in the codebase:
  - `ContentScorer.php` — unused content scoring class.
  - `ClusterEngine.php` — unused cluster registry.
  - `InternalLinker.php` — unused internal link injector.
  - `KeywordEngine.php` (PHP) — functionality handled by Python `keyword_engine.py`.
- `logs/README.md` — placeholder in otherwise empty directory.
- `.github/images/README.md` — specification for non-existent screenshot.

## [4.1.0] — 2026-04-04

### Removed
- One-time implementation reports (`AUTONOMOUS-IMPLEMENTATION-STATUS.md`, `IMPLEMENTATION-SUMMARY-v4.md`, `PRODUCTION-TEST-REPORT.md`).
- Empty brand-asset placeholder directories (`animated/`, `app-icons/`, `favicon/`, `social/`, `source-files/`) that contained only specification READMEs with no actual assets.
- `.github/images/PLACEHOLDER.md`.
- Duplicate theme README (`README-v4.md`) — content merged into `theme/pearblog-theme/README.md`.

### Fixed
- GitHub Actions `run-roadmap.yml` cache key now uses `hashFiles('data/**')` instead of `github.run_id`, so automation state is properly restored between runs.

### Changed
- `DOCUMENTATION-INDEX.md` rewritten — streamlined, accurate document map with language and audience tags.
- `README.md` updated to reflect current repository structure (removed references to deleted files).

## [4.0.0] — 2026-04-04

### Added
- Full autonomous production pipeline (7-step, WP-Cron, ~55 sec/article).
- DALL-E 3 featured image generation with 4 visual styles.
- Canonical image support: Open Graph, Twitter Card, Schema.org `ImageObject`.
- Multi-language travel content engine (PL / EN / DE).
- `MultiLanguageTravelBuilder` and `BeskidyPromptBuilder` prompt builders.
- `ContentValidator` with 3 validation modes (generic / travel / beskidy).
- `ImageGenerator` class with automatic WordPress media library integration.
- SaaS CTA monetization via `MonetizationEngine` v3.
- REST API at `pearblog/v1/automation/` (create-content, process-content, status).
- GitHub Actions workflows: `content-pipeline.yml` (daily), `run-roadmap.yml` (weekly).
- Python automation scripts: keyword engine, SERP analyzer, scraping engine, orchestrator.
- Comprehensive documentation suite (SETUP, BUSINESS-STRATEGY, MARKETING-GUIDE, etc.).

## [3.0.0]

### Added
- Basic autonomous content generation via `ContentPipeline`.
- WordPress Multisite support (`TenantContext`, `SiteProfile`, `CronManager`).
- Affiliate integration: Booking.com + Airbnb with REST tracking.
- `PromptBuilder` and `TravelPromptBuilder` content generators.
- SEO engine with automatic meta tags and Schema.org.
- AdSense ad injection and revenue tracking.
- Theme: SEO-first layout, TOC, FAQ blocks, dark mode.

### Added - Missing Theme Functions
- **`pearblog_extract_location_from_content()`** — Extract location from article title/content using pattern matching for Polish/European travel destinations (monetization.php:344-378)
- **`pearblog_affiliate_box()`** — Render affiliate monetization boxes with fallback support for Booking.com, Airbnb, and SaaS CTAs (monetization.php:380-450)
- **`pearblog_get_saas_cta()`** — Match post content to SaaS products using keyword scoring algorithm (monetization.php:452-500)
- **`pb_get_headline_variant()`** — A/B testing function that serves headline variants and tracks impressions with session-based consistency (ab-testing-metabox.php:197-258)
- **`pb_track_ab_impression()`** and **`pb_track_ab_click()`** — Track A/B test metrics with daily and lifetime aggregates (ab-testing-metabox.php:260-312)
- **`pb_get_ab_test_results()`** — Calculate A/B test results with CTR and automatic winner detection (ab-testing-metabox.php:314-352)
- **`pb_check_ab_test_winner()`** — Auto-declare winner after 100+ impressions with 10% CTR difference threshold (ab-testing-metabox.php:354-387)

### Added - Security Features
- **Rate limiting for lead submissions** — `pearblog_lead_permission_check()` function limits submissions to 5 per IP per hour using WordPress transients (lead-generation.php:121-162)
- **IP-based rate limiting** — Prevents spam and DoS attacks on public REST API endpoints

### Fixed - Critical Security Vulnerabilities
- **SQL injection in behavior-tracking.php** — Fixed table name interpolation using `$wpdb->prepare()` and `esc_like()` for SHOW TABLES query (behavior-tracking.php:32-36)
- **Permission callback on lead endpoint** — Changed from `__return_true` to `pearblog_lead_permission_check` with rate limiting (lead-generation.php:63)

### Changed
- **Theme version** — Updated from 4.0.0 to 5.0.0 for major feature additions
- **A/B testing integration** — Complete implementation with automatic winner detection and post title updates

### Fixed - Theme Functionality
- **Missing function calls** — All 6 undefined function calls in single.php now properly implemented
- **A/B testing meta box** — Now fully functional with result tracking and winner selection

## [4.3.0] — 2026-04-04

### Added
- `mu-plugins/pearblog-engine/README.md` — plugin architecture documentation covering all 11 modules, WordPress options, filters, and REST API.
- `scripts/README.md` — Python automation suite documentation with module details, environment variables, and usage examples.

### Fixed
- **Workflow:** `run-roadmap.yml` git rebase now falls back to merge on conflict instead of silently aborting.

### Changed
- `.gitignore` — added `.env` and `.env.*` patterns for secret protection.
- `logs/.gitkeep` replaced with `logs/.gitignore` (properly ignores log files while keeping directory).
- `DOCUMENTATION-INDEX.md` updated to reference new documentation and bumped to v4.3.

## [4.2.0] — 2026-04-04

### Fixed
- **CRITICAL:** `SEOEngine::apply()` — `compact()` call with `=>` syntax caused PHP parse error, breaking the entire content pipeline. Replaced with array literal.

### Added
- `template-parts/block-tldr.php` — missing template part called from `dynamic-content.php` for TL;DR rendering.

### Removed
- Dead code PHP classes never instantiated anywhere in the codebase:
  - `ContentScorer.php` — unused content scoring class.
  - `ClusterEngine.php` — unused cluster registry.
  - `InternalLinker.php` — unused internal link injector.
  - `KeywordEngine.php` (PHP) — functionality handled by Python `keyword_engine.py`.
- `logs/README.md` — placeholder in otherwise empty directory.
- `.github/images/README.md` — specification for non-existent screenshot.

## [4.1.0] — 2026-04-04

### Removed
- One-time implementation reports (`AUTONOMOUS-IMPLEMENTATION-STATUS.md`, `IMPLEMENTATION-SUMMARY-v4.md`, `PRODUCTION-TEST-REPORT.md`).
- Empty brand-asset placeholder directories (`animated/`, `app-icons/`, `favicon/`, `social/`, `source-files/`) that contained only specification READMEs with no actual assets.
- `.github/images/PLACEHOLDER.md`.
- Duplicate theme README (`README-v4.md`) — content merged into `theme/pearblog-theme/README.md`.

### Fixed
- GitHub Actions `run-roadmap.yml` cache key now uses `hashFiles('data/**')` instead of `github.run_id`, so automation state is properly restored between runs.

### Changed
- `DOCUMENTATION-INDEX.md` rewritten — streamlined, accurate document map with language and audience tags.
- `README.md` updated to reflect current repository structure (removed references to deleted files).

## [4.0.0] — 2026-04-04

### Added
- Full autonomous production pipeline (7-step, WP-Cron, ~55 sec/article).
- DALL-E 3 featured image generation with 4 visual styles.
- Canonical image support: Open Graph, Twitter Card, Schema.org `ImageObject`.
- Multi-language travel content engine (PL / EN / DE).
- `MultiLanguageTravelBuilder` and `BeskidyPromptBuilder` prompt builders.
- `ContentValidator` with 3 validation modes (generic / travel / beskidy).
- `ImageGenerator` class with automatic WordPress media library integration.
- SaaS CTA monetization via `MonetizationEngine` v3.
- REST API at `pearblog/v1/automation/` (create-content, process-content, status).
- GitHub Actions workflows: `content-pipeline.yml` (daily), `run-roadmap.yml` (weekly).
- Python automation scripts: keyword engine, SERP analyzer, scraping engine, orchestrator.
- Comprehensive documentation suite (SETUP, BUSINESS-STRATEGY, MARKETING-GUIDE, etc.).

## [3.0.0]

### Added
- Basic autonomous content generation via `ContentPipeline`.
- WordPress Multisite support (`TenantContext`, `SiteProfile`, `CronManager`).
- Affiliate integration: Booking.com + Airbnb with REST tracking.
- `PromptBuilder` and `TravelPromptBuilder` content generators.
- SEO engine with automatic meta tags and Schema.org.
- AdSense ad injection and revenue tracking.
- Theme: SEO-first layout, TOC, FAQ blocks, dark mode.
