# Changelog

All notable changes to PearBlog Engine are documented in this file.

## [6.0.0] — 2026-04-05

### Added — AIClient v6 (Resilient AI Layer)
- **Exponential backoff with jitter** — Up to 3 retries for transient failures (5xx, 429 rate-limit, timeout). Base delay: 2 s × 2^attempt + random jitter.
- **Circuit breaker** — After 5 consecutive failures the client refuses requests for 300 s (configurable). State stored in `pearblog_circuit_failures` and `pearblog_circuit_opened_at` options. Half-open probe after cooldown expires. Reset via `AIClient::reset_circuit()`.
- **Cost tracking** — Cumulative token usage tracked in `pearblog_ai_cost_cents` option (GPT-4o-mini: $0.015¢/1K input, $0.06¢/1K output). Accessible via `AIClient::total_cost_cents()`.
- **Filters** — `pearblog_ai_request_args` (customise HTTP args), `pearblog_ai_response` (post-process generated text).
- **HTTP status codes** — Exception codes now carry the HTTP status (e.g. 429, 503) for smarter retry logic.

### Added — ContentPipeline v6 (12-Step Flow)
- **Step 3: Duplicate check** — Content fingerprinted using word trigrams; if Jaccard similarity ≥ 80 % with any of the last 200 posts, the article is skipped. Controlled via `pearblog_duplicate_check_enabled` option (default: true). Hook: `pearblog_pipeline_duplicate`.
- **Step 7: Internal linker** — New `SEO\InternalLinker` scans 50 most recent published posts for keyword overlap with the new article; injects up to 5 contextual `<a>` tags (class `pearblog-internal-link`). Stores `_pearblog_internal_links_count` post meta. Hook: `pearblog_internal_links_added`.
- **Step 9: Duplicate index** — Stores the content fingerprint for future duplicate checks (rolling window of 200).
- **Step 10: Quality scoring** — Runs `ContentValidator` and computes a `ContentScore` (Length 0–40 + Structure 0–40 + Quality 0–20 = 0–100). Stores `_pearblog_quality_score` and `_pearblog_quality_issues` post meta.
- **Step 11: Quality gate** — Articles scoring below `pearblog_min_quality_score` (default: 50) are saved as `draft` instead of `publish`.
- **Pipeline timestamp** — `pearblog_last_pipeline_run` option updated at end of every run (fixes DashboardWidget "Last pipeline run: Never").
- **AI image tracking** — Sets `_pearblog_ai_image = '1'` on the post when a featured image is generated (fixes DashboardWidget AI Images counter).

### Added — Monitoring & Health
- **`Monitoring\AlertManager`** — Dispatches operational alerts via Slack (Incoming Webhook), Discord (Webhook), and WordPress email. Registered in `Plugin::boot()`. Three convenience methods: `critical()`, `warning()`, `info()`. Settings: `pearblog_alert_slack_webhook`, `pearblog_alert_discord_webhook`, `pearblog_alert_email`. Hook: `pearblog_alert_sent`.
- **`Monitoring\HealthController`** — REST endpoint `GET /pearblog/v1/health` returning JSON: queue status, pipeline timing, circuit breaker state, AI cost, system info, and issue list. Auth: WP admin or `X-PearBlog-Key` header. Returns HTTP 503 when circuit breaker is open.

### Added — Admin Panel v6
- **SEO tab** — New tab between AI Images and Monetization. Contains: Duplicate Check toggle, Min Quality Score input (0–100), Internal Linking description.
- **Monitoring settings** — Added to Automation tab: Slack Webhook URL, Discord Webhook URL, Alert Email.
- **Health endpoint** in REST API reference table (`GET /pearblog/v1/health`).
- **Version badge** updated to v6.0.

### Added — PHPUnit Test Suite
- **`phpunit.xml`** — PHPUnit 10.5 configuration.
- **`tests/php/bootstrap.php`** — WordPress function stubs (get_option, update_post_meta, wp_strip_all_tags, etc.) + PSR-4 autoloader. Tests run without full WP environment.
- **`composer.json`** — Dev dependency on phpunit/phpunit ^10.5. Scripts: `composer test`, `composer test:unit`.
- **4 test classes, 30 tests, 72 assertions:**
  - `ContentScoreTest` (8 tests) — Perfect score, below/exact min, clamping, issues, summary format.
  - `SEOEngineTest` (7 tests) — Meta extraction, HTML H1, missing meta/title, post meta storage, canonical URL.
  - `ContentValidatorTest` (6 tests) — Valid content, missing meta/H1, short content, travel sections, AI cliché detection, report format.
  - `TopicQueueTest` (9 tests) — Empty queue, push/pop, peek, clear, FIFO order, empty strings, re-indexing, site isolation.

### Changed — Plugin Version
- `PEARBLOG_ENGINE_VERSION` bumped to `6.0.0`.
- Settings registrations now total **24**: 6 general + 2 image + 2 SEO + 4 monetization + 5 email + 2 automation + 3 monitoring.

## [5.2.0] — 2026-04-05

### Added — Admin Panel v5.2
- **Tab-based top-level Admin Menu** — `AdminPage` now registers via `add_menu_page()` (top-level, icon `dashicons-rest-api`, position 25) with 6 tabs: **General, AI Images, Monetization, Email, Automation, Queue**. Tab state persists in the URL (`?tab=`) and is switchable without page reload via inline JS.
- **Autonomous Mode setting** — New checkbox setting `pearblog_autonomous_mode` controls whether the cron manager publishes automatically from the queue.
- **Admin CSS file** — `assets/css/admin.css` (v5.1.0) loaded via `wp_enqueue_style` on the Engine page only (replaces inline `wp_add_inline_style`).
- **REST API reference table** — Automation tab shows the three `/pearblog/v1/automation/` endpoints with method and description.

### Fixed — Admin Panel
- **`esp_provider` sanitize callback** changed from `sanitize_text_field` to `sanitize_key` (correct for enum-type select values).
- **Redirect URL** for queue form actions now points to `admin.php` (top-level menu) instead of `options-general.php` (Settings submenu).
- **`enqueue_admin_assets` hook suffix** corrected from `settings_page_pearblog-engine` to `toplevel_page_pearblog-engine`.

### Added — Engine Dashboard Widget
- **`DashboardWidget`** class (`src/Admin/DashboardWidget.php`) registered in `Plugin::boot()`. Shows 4 KPIs in a 2×2 grid: Queue size, Posts Published Today (green), AI Images count, Missing Alt Texts (color-coded: green/amber/red). Footer displays last pipeline run time and a link to the Engine settings page.

### Added — Frontend Header v5.1
- **Sticky header** — `#pb-header` with class `pb-nav--sticky` (CSS: `position: sticky; top: 0; z-index: 1000`).
- **Reading progress bar** — `#pb-reading-progress-bar` fixed at top of viewport, driven by `app.js` scroll handler.
- **Search panel** — `#pb-search-panel` overlay with `get_search_form()`, toggled via `#pb-search-toggle` button; dismiss via ✕ button or Escape key.
- **Dark mode toggle** — `#pb-dark-mode-toggle` icon button (sun/moon SVG icons) in the nav actions area.
- **Google Fonts** — `pearblog-fonts` stylesheet (`Poppins:600;700` + `Inter:400;500;600`) enqueued in `pearblog_enqueue_assets()`.

### Added — Missing Theme Templates
- **`page.php`** — Static page template with sidebar support, `wp_link_pages()` for paginated pages.
- **search.php** — Search results page with result count, card grid, pagination, and empty-state with search form.
- **`404.php`** — Not-found page with large 404 code, search form, and recent posts fallback list.

### Added — CSS Components
- `#pb-reading-progress-bar`, `#pb-header.pb-nav--sticky`, `.pb-nav-actions`, `.pb-search-toggle`, `.pb-dark-mode-toggle`, `#pb-search-panel`, `.pb-error-code` styles added to `components.css`.

## [5.1.0] — 2026-04-04

### Removed — Dead Code
- **`main.js`** — Deleted unused frontend JS file; all functionality (lazy loading, FAQ accordion, mobile menu, smooth scroll, Web Vitals) already present in `app.js` which is the only enqueued script.

### Fixed — Dashboard Backend
- **Missing email marketing settings registration** — `pearblog_esp_provider`, `pearblog_mailchimp_api_key`, `pearblog_mailchimp_list_id`, `pearblog_convertkit_api_key`, `pearblog_convertkit_form_id` were rendered in the admin form but never registered via `register_setting()`, preventing values from being saved (AdminPage.php).

### Improved — Dashboard Widget
- **Performance** — `wp_count_posts()` result is now stored in a variable instead of calling the function three times.
- **Analytics link** — Added "Analytics" quick-action button linking to PB Analytics dashboard page.

### Improved — Admin Settings Page
- **Styling** — Added admin CSS for the Engine Settings page: max-width container, section borders, styled profile box and scrollable queue list for better usability.

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
