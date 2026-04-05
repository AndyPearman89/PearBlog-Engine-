# Changelog

All notable changes to PearBlog Engine are documented in this file.

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
