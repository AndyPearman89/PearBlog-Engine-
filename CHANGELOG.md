# Changelog

All notable changes to PearBlog Engine are documented in this file.

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
