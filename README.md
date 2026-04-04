# PearBlog Engine PRO

**Autonomous AI content production system for WordPress.**

📚 **[Documentation Index →](DOCUMENTATION-INDEX.md)** · 📋 **[Changelog →](CHANGELOG.md)**

---

## What It Does

PearBlog Engine generates, optimizes, and publishes SEO articles autonomously — every hour via WP-Cron — with zero manual intervention.

**Pipeline (7 steps, ~55 sec, $0.08/article):**

```
Topic Queue → PromptBuilder → GPT-4o-mini → SEO Engine
  → MonetizationEngine → DALL-E 3 Image → Publish
```

---

## Quick Start

```bash
# 1. Copy plugin + theme into WordPress
cp -r mu-plugins/pearblog-engine /path/to/wp-content/mu-plugins/
cp -r theme/pearblog-theme       /path/to/wp-content/themes/

# 2. Set API key (wp-config.php or WP Admin)
define('PEARBLOG_OPENAI_API_KEY', 'sk-...');

# 3. Add topics to queue → pipeline runs automatically every hour
```

See **[SETUP.md](SETUP.md)** for GitHub Actions setup and **[AUTONOMOUS-ACTIVATION-GUIDE.md](AUTONOMOUS-ACTIVATION-GUIDE.md)** for full activation.

---

## Repository Structure

```
PearBlog-Engine/
├── mu-plugins/pearblog-engine/          # Core WordPress MU-plugin
│   ├── pearblog-engine.php              # Bootstrap (PSR-4 autoload)
│   └── src/
│       ├── Pipeline/ContentPipeline.php # 7-step autonomous flow
│       ├── AI/AIClient.php              # GPT-4o-mini integration
│       ├── AI/ImageGenerator.php        # DALL-E 3 featured images
│       ├── Content/                     # 4 prompt builders + validator
│       ├── SEO/SEOEngine.php            # Meta tags, Schema.org
│       ├── Monetization/               # AdSense + SaaS CTA injection
│       ├── Scheduler/CronManager.php   # WP-Cron + multisite
│       ├── Admin/AdminPage.php         # WP Admin settings
│       ├── API/AutomationController.php# REST API endpoints
│       ├── Keywords/                   # Keyword clustering
│       └── Tenant/                     # Multi-site context
│
├── theme/pearblog-theme/               # SEO-first WordPress theme
│   ├── single.php                      # 12-element SEO layout
│   ├── inc/                            # 16 modules (monetization, analytics, etc.)
│   ├── template-parts/                 # Reusable blocks
│   └── assets/                         # CSS + JS (AI personalization)
│
├── scripts/                            # Python automation (optional)
│   ├── automation_orchestrator.py      # Full-cycle orchestration
│   ├── keyword_engine.py              # Keyword research
│   ├── scraping_engine.py             # SERP data extraction
│   ├── serp_analyzer.py               # Competition analysis
│   └── run_pipeline.py                # GitHub Actions runner
│
├── examples/                           # Usage examples
├── brand-assets/                       # Brand guidelines
├── SETUP.md                            # Installation guide
├── BUSINESS-STRATEGY.md                # ROI & monetization strategy
├── MARKETING-GUIDE.md                  # SEO & traffic acquisition
├── TRAVEL-CONTENT-ENGINE.md            # Specialized travel builders
├── PRODUCTION-ANALYSIS-FULL.md         # Complete operations manual
└── CHANGELOG.md                        # Release history
```

---

## Content Generation Engines

| Builder | Use Case | Language |
|---------|----------|----------|
| `PromptBuilder` | Generic SEO content for any industry | configurable |
| `TravelPromptBuilder` | Structured travel content with mandatory sections | configurable |
| `BeskidyPromptBuilder` | Beskidy mountains — weather + day planner | PL |
| `MultiLanguageTravelBuilder` | Culturally localized travel content | PL / EN / DE |

Builder selection is automatic via `PromptBuilderFactory` (based on industry keywords). Override with the `pearblog_prompt_builder_class` filter.

---

## Theme Features

- **SEO layout:** H1 → TL;DR → Ads → Affiliate → TOC → Content → FAQ → Related
- **AI Personalization (v4):** Dynamic headlines, CTAs, and recommendations based on user context
- **A/B Testing:** Automatic headline testing with daily winner detection
- **Monetization:** Auto ad injection, affiliate priority (Booking → Airbnb → fallback), SaaS CTA
- **Performance:** Lazy loading, Core Web Vitals, ~8 KB personalization JS
- **Multisite:** Per-site branding, colours, feature toggles

---

## Key Metrics

| Metric | Value |
|--------|-------|
| Cost per article (with image) | $0.08 |
| Pipeline execution time | ~55 sec |
| Articles / month (rate=1) | 720 |
| Monthly cost (720 articles) | ~$58 |
| Break-even traffic | ~5,000 visitors/mo |
| Automation level | 100% |

---

## Documentation

| Document | What It Covers |
|----------|----------------|
| [SETUP.md](SETUP.md) | GitHub Actions & initial configuration |
| [AUTONOMOUS-ACTIVATION-GUIDE.md](AUTONOMOUS-ACTIVATION-GUIDE.md) | Step-by-step autonomous launch (PL) |
| [PRODUCTION-ANALYSIS-FULL.md](PRODUCTION-ANALYSIS-FULL.md) | Complete operations manual (PL) |
| [BUSINESS-STRATEGY.md](BUSINESS-STRATEGY.md) | ROI, monetization, scaling |
| [MARKETING-GUIDE.md](MARKETING-GUIDE.md) | SEO, traffic, affiliate strategy |
| [TRAVEL-CONTENT-ENGINE.md](TRAVEL-CONTENT-ENGINE.md) | Specialised travel prompt builders |
| [theme/pearblog-theme/README.md](theme/pearblog-theme/README.md) | Theme features & configuration |

---

## Tech Stack

- **PHP 7.4+** · WordPress 5.9+ · Vanilla JS · CSS Variables
- **AI:** OpenAI GPT-4o-mini (content) + DALL-E 3 (images)
- **Python 3.11** (optional automation scripts)

## License

GNU General Public License v2 or later
