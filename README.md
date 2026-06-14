# PearBlog Engine PRO

**Autonomous AI content production system for WordPress — v8.0**

**🚀 NEW: [Poradnik.pro Decision Platform →](PORADNIK-PRO-PLATFORM.md)** — Transform your content into a full decision engine with comparisons, rankings, calculators, expert marketplace, and lead generation.

📚 **[Documentation Index →](DOCUMENTATION-INDEX.md)** · 📋 **[Changelog →](CHANGELOG.md)** · ⚙️ **[Setup →](SETUP.md)** · 🗺️ **[Roadmap →](ROADMAP-VISUAL.md)** · 📊 **[Progress →](PROGRESS-VISUALIZATION.md)** · 🤖 **[Autopilot →](ENTERPRISE-AUTOPILOT-TASKLIST.md)**

---

## Screenshots & Diagrams

| Diagram | Preview |
|---------|---------|
| [Pipeline Overview](brand-assets/screenshots/pipeline-overview.svg) | 12-step autonomous content pipeline with metrics |
| [System Architecture](brand-assets/screenshots/architecture.svg) | All 16 modules, external integrations, WordPress core |
| [Admin Panel — General](brand-assets/screenshots/admin-panel.svg) | WP Admin tabbed settings UI mockup |
| [Queue Manager](brand-assets/screenshots/queue-manager.svg) | Topic queue, pipeline trigger, add-topics form |
| [Monitoring Dashboard](brand-assets/screenshots/monitoring-dashboard.svg) | Metric cards, alert history table, action buttons |

![Pipeline Overview](brand-assets/screenshots/pipeline-overview.svg)

---

## What It Does

PearBlog Engine generates, optimizes, and publishes SEO articles autonomously — every hour via WP-Cron — with zero manual intervention.

**Pipeline (12 steps, ~55 sec, $0.08/article):**

```
Topic Queue → PromptBuilderFactory → AIClient (GPT-4o-mini) → DuplicateDetector → Draft
  → SEOEngine → MonetizationEngine → InternalLinker → ImageGenerator (DALL-E 3)
  → DuplicateIndex → Publish → QualityScorer + AlertManager
```

### 🚀 NEW: Decision Platform (Poradnik.pro)

Transform your content site into an Enterprise Decision Platform:

- **Comparisons** — AI-generated comparison pages (Product A vs B)
- **Rankings** — TOP lists with local filtering (Best services in {city})
- **Calculators** — Interactive cost/ROI calculators
- **Expert Marketplace** — Verified service provider profiles
- **Lead Generation** — Capture and match user inquiries
- **Decision Assistant AI** — Personalized recommendations
- **Intent Detection** — Automatic content enrichment based on user intent
- **Internal Link Graph** — Automatic relationship discovery

**Complete User Journey:**
```
Problem → Knowledge → Comparison → Decision → Expert → Contact → Lead
```

**[Learn more about the Decision Platform →](PORADNIK-PRO-PLATFORM.md)**

---

## Quick Start

```bash
# 1. Copy plugin + theme into WordPress
cp -r mu-plugins/pearblog-engine /path/to/wp-content/mu-plugins/
cp -r theme/pearblog-theme       /path/to/wp-content/themes/

# 2. Set API key (wp-config.php or WP Admin → PearBlog Engine)
define('PEARBLOG_OPENAI_API_KEY', 'sk-...');

# 3. Add topics in the admin queue → pipeline runs automatically every hour
```

For local repository checks during development:

```bash
./run dev
```

See **[SETUP.md](SETUP.md)** for GitHub Actions setup and **[AUTONOMOUS-ACTIVATION-GUIDE.md](AUTONOMOUS-ACTIVATION-GUIDE.md)** for full activation.

---

## Repository Structure

```
PearBlog-Engine/
├── mu-plugins/pearblog-engine/          # Core WordPress MU-plugin (v8.0, 216 PHP files)
│   ├── pearblog-engine.php              # Bootstrap (PSR-4 autoload)
│   ├── assets/css/admin.css             # Admin panel styles
│   └── src/
│       ├── Core/Plugin.php              # Singleton boot — registers all sub-systems
│       ├── Pipeline/                    # 12-step autonomous pipeline (ContentPipeline, BackgroundProcessor, ContentImportExport, PipelineAuditLog, ApprovalWorkflow)
│       ├── AI/                          # Multi-provider AI layer (OpenAI/Anthropic/Gemini, DALL-E 3, ContentRewriter, FactChecker, PromptOptimizer)
│       ├── Content/                     # 28+ builders + ContentValidator, DuplicateDetector, QualityScorer, ReadabilityAnalyzer, ContentRefreshEngine
│       ├── Analytics/                   # GA4Client, AnalyticsDashboard, SearchIntentEngine, PredictiveEngine, CohortEngine, ContentROIEngine
│       ├── SEO/                         # SEOEngine, ProgrammaticSEO, SchemaManager, InternalLinker, TopicalAuthorityEngine, XmlSitemapManager, HreflangManager
│       ├── Monetization/               # AdSense + Affiliate + SaaS CTA injection
│       ├── Scheduler/                   # CronManager + PublishScheduler (GA4-optimal timing) + TimeZoneScheduler
│       ├── Keywords/                    # KeywordCluster + KeywordClusterEngine (IDF-based GA4 clustering)
│       ├── Cache/                       # ContentCache + ObjectCacheAdapter + CdnManager (BunnyCDN/Cloudflare) + QueryOptimizer
│       ├── API/                         # REST endpoints — AutomationController, DashboardController, TopicsController, GraphQLController, PermissionManager, RateLimiter
│       ├── Admin/                       # 20 classes — AdminPageV8Enterprise (15-tab), ContentCalendar, OnboardingWizardV2, WhiteLabelManager
│       ├── Monitoring/                  # AlertManager + HealthController + PerformanceDashboard + Logger + SLAManager + ErrorTracker
│       ├── Social/                      # SocialPublisher + SocialCalendar + PushNotificationPublisher
│       ├── CLI/                         # PearBlogCommand + AutopilotRunner (26 tasks, 7 phases) + IntegrationCommand + SEOV3Command + SecurityCommand
│       ├── Tenant/                      # Multi-site context (TenantContext + SiteProfile)
│       ├── LeadAI/                      # PT24 AI Lead Engine V2 — DDD (Domain / Application / Infrastructure / UI)
│       ├── Poradnik/                    # Poradnik.pro content engine (PoradnikEngine, AIOptimizer, ScoringEngine, DecisionEngine)
│       ├── Security/                    # RBACManager, SecurityAuditor, ContentModerator, PIIDetector, ComplianceExporter
│       ├── DecisionPlatform/            # Full Decision Platform — comparisons, rankings, calculators, expert marketplace, lead gen, intent detection
│       ├── Distribution/               # AMPGenerator + RSSFeedBuilder
│       ├── Email/                       # EmailDigest + NewsletterBuilder
│       ├── Integration/                 # PT24Bridge + ZapierManager + CTAInjector + ContentLinker
│       ├── Logging/                     # AdvancedLogger + DatabaseHandler + context processors
│       ├── Database/                    # PT24IntegrationSchema + PoradnikSchema + PoradnikV3Schema
│       ├── Webhook/                     # WebhookManager
│       └── Testing/                     # ABTestEngine
│
├── theme/pearblog-theme/               # SEO-first WordPress theme v5.2
│   ├── index.php                       # Homepage with hero + card grid
│   ├── single.php                      # 12-element SEO article layout
│   ├── page.php                        # Static page template
│   ├── search.php                      # Search results template
│   ├── 404.php                         # Error page
│   ├── category.php                    # Category archive
│   ├── inc/                            # 17 modules (monetization, analytics, PT24, etc.)
│   ├── template-parts/                 # 13 reusable block templates
│   └── assets/
│       ├── css/                        # base, components, utilities, admin styles
│       └── js/                         # app, lazyload, personalization
│
├── scripts/                            # Python automation (optional)
│   ├── automation_orchestrator.py      # Full-cycle orchestration
│   ├── keyword_engine.py              # Keyword research
│   ├── scraping_engine.py             # SERP data extraction
│   ├── serp_analyzer.py               # Competition analysis
│   └── run_pipeline.py                # GitHub Actions runner
│
├── clients/                            # API client libraries
│   ├── js/pearblog-client.js           # ESM JavaScript API client
│   └── python/pearblog_client.py       # Python API client
│
├── tests/load/                         # k6 load testing scenarios
├── examples/                           # Usage examples
├── brand-assets/                       # Brand guidelines & assets
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

## Programmatic SEO (v5.1)

Automated SEO applied to every article, no plugins required:

| Feature | Description |
|---------|-------------|
| **Schema.org JSON-LD** | Article + BreadcrumbList + FAQPage structured data |
| **Open Graph** | og:title, og:description, og:image (1792×1024) |
| **Twitter Cards** | summary_large_image with auto-populated fields |
| **Auto Meta Descriptions** | Generated from content when AI doesn't produce one |
| **Keyword Density** | Analyses and reports density for target keywords |
| **Bulk SEO Audit** | Scans all posts for issues (thin content, missing H2, etc.) |
| **Internal Link Suggestions** | Keyword-based related post discovery |

---

## Image Generator & Analyzer (v5.1)

| Feature | Description |
|---------|-------------|
| **DALL-E 3 generation** | Generates featured images from article titles/keywords |
| **Keyword-based prompts** | Admin UI to generate images from custom keyword sets |
| **Batch generation** | Detect and fill posts missing featured images |
| **Media audit** | Summary: total images, AI-generated, missing alt texts |
| **Alt text auto-fix** | Bulk-generate alt texts from image titles/filenames |
| **Oversized image detection** | Flags images exceeding recommended dimensions |

---

## Admin Panel (v5.1)

Top-level **PearBlog Engine** menu in WordPress admin with tabbed sections:

- **General** — API keys, niche, tone, language, publish rate
- **AI Images** — DALL-E 3 toggle, style, batch generation, media audit
- **Programmatic SEO** — Audit results, auto-fix meta descriptions
- **Monetization** — AdSense, Booking.com, SaaS CTA products (JSON)
- **Email** — Mailchimp / ConvertKit integration
- **Queue** — Topic queue management (add / view / clear)

---

## Theme Features (v5.1)

- **SEO layout:** H1 → TL;DR → Ads → Affiliate → TOC → Content → FAQ → Related
- **Reading progress bar** — Sticky top indicator that fills as user scrolls
- **Dark mode** — Toggle button in header; respects `prefers-color-scheme`
- **Search panel** — Slide-down search form triggered from header icon
- **Sticky header** — Shrinks on scroll, stays at top with shadow
- **AI Personalization (v4):** Dynamic headlines, CTAs, recommendations
- **A/B Testing:** Automatic headline testing with daily winner detection
- **Monetization:** Auto ad injection, affiliate priority (Booking → Airbnb), SaaS CTA
- **Performance:** Lazy loading, Core Web Vitals, ~8 KB personalization JS
- **PT24 integration:** Custom post type for service landing pages with city/service routing
- **Missing templates added:** `page.php`, `search.php`, `404.php`
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
| [DEPLOYMENT.md](DEPLOYMENT.md) | Production deployment guide (EN) — Apache/Nginx, SSL, Git, CI/CD, 4 hosting providers |
| [DEPLOYMENT-PL.md](DEPLOYMENT-PL.md) | Przewodnik wdrożenia produkcyjnego (PL) |
| [PROGRESS-VISUALIZATION.md](PROGRESS-VISUALIZATION.md) | Bilingual progress visualization — milestones, timeline, metrics (PL + EN) |
| [AUTONOMOUS-ACTIVATION-GUIDE.md](AUTONOMOUS-ACTIVATION-GUIDE.md) | Step-by-step autonomous launch (PL) |
| [PRODUCTION-ANALYSIS-FULL.md](PRODUCTION-ANALYSIS-FULL.md) | Complete operations manual (PL) |
| [BUSINESS-STRATEGY.md](BUSINESS-STRATEGY.md) | ROI, monetization, scaling |
| [MARKETING-GUIDE.md](MARKETING-GUIDE.md) | SEO, traffic, affiliate strategy |
| [TRAVEL-CONTENT-ENGINE.md](TRAVEL-CONTENT-ENGINE.md) | Specialised travel prompt builders |
| [theme/pearblog-theme/README.md](theme/pearblog-theme/README.md) | Theme features & configuration |
| [mu-plugins/pearblog-engine/README.md](mu-plugins/pearblog-engine/README.md) | Plugin architecture & filters |
| [scripts/README.md](scripts/README.md) | Python automation suite |

---

## Tech Stack

- **PHP 8.0+** · WordPress 6.0+ · Vanilla JS · CSS Custom Properties
- **AI:** OpenAI GPT-4o-mini (content) + DALL-E 3 (images)
- **Fonts:** Poppins (display) · Inter (UI) · JetBrains Mono (code)
- **Python 3.11** (optional automation scripts)

## License

GNU General Public License v2 or later
