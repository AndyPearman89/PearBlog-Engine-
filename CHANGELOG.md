# Changelog

All notable changes to PearBlog Engine are documented in this file.

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
