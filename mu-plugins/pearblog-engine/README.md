# ⚙️ PearBlog Engine — WordPress MU-Plugin v9.0

> Enterprise-grade autonomous AI content production, analytics, and monetization engine for WordPress.

---

## Quick Start

Drop `mu-plugins/pearblog-engine/` into your WordPress `wp-content/mu-plugins/` directory.
The plugin bootstraps automatically — no activation required.

```php
// wp-config.php (required)
define('PEARBLOG_OPENAI_API_KEY', 'sk-...');
```

Then add topics in **WP Admin → PearBlog Engine → Queue** and the pipeline runs every hour automatically.

### Development

```bash
./run dev   # PHP syntax check across src/
```

### Tests

```bash
cd mu-plugins/pearblog-engine
composer install
vendor/bin/phpunit
```

**Test suite:** 1 760 tests, 0 failures (as of v9.0 session 11).

---

## Architecture Overview

```
pearblog-engine.php              # Bootstrap & PSR-4 autoloader
assets/css/admin.css             # Admin panel styles
src/
├── Core/                        # Plugin singleton, DistributedLockManager
├── Pipeline/                    # Content pipeline, async queue, approval workflow
├── AI/                          # Multi-provider AI (OpenAI, Anthropic, Gemini), images, video, facts
├── Content/                     # Prompt builders, validators, refresh, calculators, collaboration
├── SEO/                         # Schema.org, Core Web Vitals, orphan detection, internal linking
├── Monetization/                # CRO engine, paywalls, affiliates, revenue tracking
├── Analytics/                   # Predictive analytics, cohorts, conversion tracking, ROI
├── DecisionPlatform/            # Quiz engine, rankings, comparisons, lead generation
├── API/                         # REST + GraphQL controllers, mobile backend
├── Admin/                       # Admin pages (v5/v7/v8 Enterprise), tabs, widgets
├── Tenant/                      # Multi-tenant: isolation, billing, onboarding
├── Security/                    # RBAC, PII detection, compliance export, content moderation
├── LeadAI/                      # AI-powered lead management (DDD architecture)
├── Email/                       # Newsletter builder, email digest
├── Social/                      # Social publisher, push notifications
├── Distribution/                # AMP generator, RSS feeds
├── Scheduler/                   # Cron, publish scheduling, timezone support
├── Cache/                       # CDN, object cache, query optimizer
├── Monitoring/                  # Health checks, alerts, performance dashboard, SLA
├── Logging/                     # PSR-3-like logger with processors and handlers
├── Integration/                 # PT24 bridge, Zapier, CTA injection, link attribution
├── Keywords/                    # Keyword clustering engine
├── Poradnik/                    # Poradnik.pro platform engine
├── Testing/                     # A/B testing, AI variant generation, Bayesian optimizer
├── Webhook/                     # Outbound webhook manager
├── Database/                    # Schema migrations
├── CLI/                         # WP-CLI commands
└── ...
tests/
├── php/Unit/                    # 98 unit test files
├── php/Integration/             # 4 integration test files
└── php/bootstrap.php            # PHPUnit bootstrap with WP function stubs
```

**Total: 224 PHP source files · 33 modules · 1 760 automated tests.**

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

## Module Reference

### Core (`Core/`)

| Class | Description |
|-------|-------------|
| `Plugin` | Singleton that boots all sub-systems exactly once via `Plugin::boot()` |
| `DistributedLockManager` | Cross-process mutex using WP transients with TTL |

### AI (`AI/`) — Multi-Provider Orchestration

| Class | Description |
|-------|-------------|
| `AIClient` | OpenAI Chat Completions wrapper (GPT-4o-mini default) |
| `StreamingAIClient` | SSE streaming variant of AIClient |
| `AIProviderInterface` | Contract for all AI providers |
| `AIProviderFactory` | Instantiates providers by name |
| `OpenAIProvider` | OpenAI GPT adapter |
| `AnthropicProvider` | Claude adapter |
| `GeminiProvider` | Google Gemini adapter |
| `SmartProviderRouter` | **v9** — Content-type routing, Thompson Sampling scoring, failover, budget tracking |
| `ImageGenerator` | DALL-E 3 generation (photorealistic/illustration/artistic/minimal) |
| `ImageAnalyzer` | Media audit: missing alt, oversized images, keyword suggestions |
| `ContentRewriter` | AI-powered content rewriting/paraphrasing |
| `FactChecker` | AI fact verification with source citations |
| `VideoScriptBuilder` | Video script generation for multiple platforms |
| `PodcastGenerator` | AI podcast script/outline generation |
| `PromptOptimizer` | Prompt engineering optimization |
| `RateLimitException` | Exception for provider rate limits |

### Content (`Content/`) — 36 Classes

| Class | Description |
|-------|-------------|
| `PromptBuilder` | Base prompt builder with SEO and monetization rules |
| `PromptBuilderFactory` | Auto-selects builder based on industry keywords |
| `TravelPromptBuilder` | Travel content with mandatory sections |
| `BeskidyPromptBuilder` | Beskidy-specific with weather and day planning |
| `MultiLanguageTravelBuilder` | PL/EN/DE localization |
| `PT24PromptBuilder` | PT24 platform content builder |
| `PoradnikPromptBuilder` | Poradnik.pro content builder |
| `PoradnikV3TemplateBuilder` | Poradnik V3 template system |
| `PoradnikCostTemplateBuilder` | "Ile kosztuje" cost article templates |
| `EcommercePromptBuilder` | E-commerce product content |
| `FinancePromptBuilder` | Finance/insurance content |
| `FoodPromptBuilder` | Food/recipe content |
| `HealthPromptBuilder` | Health/medical content |
| `TechPromptBuilder` | Technology content |
| `PersonaBuilder` | Writer persona configuration |
| `ContentValidator` | Structure & quality validation (generic/travel/beskidy) |
| `ContentScore` | Content quality scoring |
| `QualityScorer` | Multi-factor quality assessment |
| `ReadabilityAnalyzer` | Readability metrics (Flesch-Kincaid etc.) |
| `DuplicateDetector` | Content plagiarism/duplication detection |
| `ContentRefreshEngine` | **v9** — Staleness detection, performance-based refresh prioritisation |
| `CompetitiveGapEngine` | Competitive content gap analysis |
| `FewShotEngine` | Few-shot learning prompt examples |
| `GlossaryBuilder` | Auto-generated glossary/terminology |
| `LivePricingDataLayer` | Real-time pricing data integration |
| `SmartCalculatorEngine` | Interactive calculator content widgets |
| `CollaborationManager` | **v9** — Multi-stage review, inline comments, editorial workflow |
| `MultilingualManager` | Multi-language content management |
| `TopicQueue` | FIFO topic queue (WP option per site) |
| `TopicCPT` | Custom Post Type for topics |
| `TopicResearchEngine` | AI-powered topic research and suggestions |
| `RelatedEntityManager` | Related content entity linking |
| `PostMetaManager` | Structured post metadata management |
| `CTABlockCPT` | Call-to-Action block CPT |
| `FAQBlockCPT` | FAQ block CPT |
| `SerpScraper` | SERP analysis for competitive intelligence |

### Pipeline (`Pipeline/`)

| Class | Description |
|-------|-------------|
| `ContentPipeline` | Main 8-step content generation pipeline |
| `AsyncQueueManager` | Background job queue (wp_cron backend) |
| `BackgroundProcessor` | Long-running background task processor |
| `ApprovalWorkflow` | Multi-stage content approval workflow |
| `ContentImportExport` | Bulk content import/export (CSV/JSON) |
| `PipelineAuditLog` | Immutable audit trail for all pipeline actions |

### SEO (`SEO/`) — 15 Classes

| Class | Description |
|-------|-------------|
| `SEOEngine` | Core SEO: title, meta, Schema.org extraction |
| `ProgrammaticSEO` | JSON-LD, Open Graph, Twitter Cards, bulk audit |
| `ProgrammaticLocalSEO` | Local business SEO (NAP, GBP, local Schema) |
| `SchemaManager` | Advanced Schema.org type management |
| `InternalLinker` | Automated internal link suggestion engine |
| `CoreWebVitalsMonitor` | **v9** — CWV tracking (LCP, FID, CLS) with alerts |
| `OrphanPageDetector` | **v9** — Orphan page scan, link graph, suggestions |
| `TopicalAuthorityEngine` | Topic cluster authority scoring |
| `HreflangManager` | Hreflang tag management for multilingual sites |
| `XmlSitemapManager` | XML sitemap generation and submission |
| `SearchConsoleClient` | Google Search Console API client |
| `KeywordDatabase` | Keyword storage and retrieval |
| `KeywordDatabaseV3` | V3 keyword database with verticals |
| `KeywordGeneratorCLI` | CLI keyword generation |
| `KeywordGeneratorV3CLI` | V3 CLI keyword generation with local SEO |

### Monetization (`Monetization/`)

| Class | Description |
|-------|-------------|
| `MonetizationEngine` | AdSense + Affiliate + SaaS CTA injection |
| `CROEngine` | **v9** — Conversion Rate Optimization engine |
| `PaywallEngine` | **v9** — Metered/hard paywall with subscriber management |
| `RevenueTracker` | **v9** — Per-post and aggregate revenue tracking |
| `AffiliateDiscovery` | **v9** — Auto-discovery of affiliate opportunities |
| `FunnelStageDetector` | Visitor funnel stage classification |

### Analytics (`Analytics/`)

| Class | Description |
|-------|-------------|
| `PredictiveAnalytics` | **v9** — Performance forecasting, anomaly detection, trend analysis |
| `PredictiveEngine` | ML-based content performance prediction |
| `CohortEngine` | Cohort & funnel analytics with weekly snapshots |
| `ContentROIEngine` | Per-article and site-wide ROI calculation |
| `ConversionFlowTracker` | Multi-stage conversion funnel tracking |
| `ConversionTracker` | Simple conversion event tracking |
| `GA4Client` | Google Analytics 4 API client |
| `SearchIntentEngine` | Search intent classification |
| `AnalyticsDashboard` | Analytics data aggregation for UI |

### DecisionPlatform (`DecisionPlatform/`) — 17 Classes

| Class | Description |
|-------|-------------|
| `DecisionPlatformManager` | Platform orchestrator and registration |
| `DecisionPlatformAPI` | REST API for decision platform |
| `QuizEngine` | Interactive quiz with lead capture |
| `RankingEngine` | Dynamic ranking generation |
| `ComparisonEngine` | Side-by-side product comparison |
| `DecisionAssistant` | AI-powered decision recommendations |
| `IntentDetector` | User purchase intent detection |
| `LeadGenerator` | Quiz-to-lead conversion |
| `LinkGraph` | Internal content relationship graph |
| `BlockRenderer` | Gutenberg block rendering for decision widgets |
| `PriceComparison` | Real-time price comparison |
| `Article` / `Calculator` / `Comparison` / `Expert` / `Offer` / `Ranking` | Domain value objects |

### API (`API/`)

| Class | Description |
|-------|-------------|
| `AutomationController` | REST `/pearblog/v1/automation/` — content creation & status |
| `GraphQLController` | **v9** — GraphQL endpoint with queue/stats/health queries |
| `MobileAPIController` | **v9** — Mobile app backend (dashboard, queue, alerts) |
| `DashboardController` | Admin dashboard data endpoint |
| `TopicsController` | Topic CRUD REST endpoint |
| `PoradnikV3API` | Poradnik V3 platform API |
| `SearchSuggestAPI` | Typeahead search suggestions |
| `PermissionManager` | REST permission/capability checks |
| `RateLimiter` | Request rate limiting (per-IP, per-key) |

### Admin (`Admin/`) — 20 Classes

| Class | Description |
|-------|-------------|
| `AdminPage` | Base admin menu (6 tabs, v5.1) |
| `AdminPageV7` | V7 admin panel redesign |
| `AdminPageV8Enterprise` | V8 Enterprise admin with advanced features |
| `DashboardWidget` | WP Dashboard pipeline stats widget |
| `ContentCalendar` | Visual content calendar UI |
| `OnboardingWizard` / `OnboardingWizardV2` | First-run setup wizards |
| `WhiteLabelManager` | White-label branding configuration |
| `DatabaseMigration` | Schema migration runner |
| Tab classes: `AnalyticsTab`, `AutomationTab`, `ContentEngineTab`, `DashboardTab`, `LeadsTab`, `MonetizationTab`, `MultisiteTab`, `PerformanceDashboardTab`, `SEOTab`, `SettingsTab`, `StrategyTab` | Individual admin tab renderers |

### Tenant (`Tenant/`) — Multi-Tenant Platform

| Class | Description |
|-------|-------------|
| `TenantContext` | Runtime context (domain + profile) for current blog |
| `TenantIsolator` | AES-256-CBC per-site option encryption with HKDF key derivation |
| `SiteProfile` | Value object: niche, strategy, language, tone |
| `BillingEngine` | **v9** — AI token metering, Stripe integration, monthly quota |
| `TenantOnboardingController` | **v9** — Agency onboarding workflow + REST endpoints |

### Security (`Security/`)

| Class | Description |
|-------|-------------|
| `SecurityAuditor` | OWASP Top 10 automated scanner with risk scoring |
| `RBACManager` | Role-Based Access Control management |
| `ContentModerator` | AI-powered content moderation (toxicity, spam) |
| `ComplianceExporter` | GDPR/CCPA data export (JSON/CSV) |
| `PIIDetector` | PII detection (email, PESEL, NIP, phone, CC, IBAN) with redaction |

### LeadAI (`LeadAI/`) — Domain-Driven Design

```
LeadAI/
├── API/LeadAIController.php          # REST endpoints
├── Application/
│   ├── LeadOrchestrator.php          # Main orchestration logic
│   ├── AIReplyService.php            # AI-generated lead responses
│   ├── LeadRoutingService.php        # Smart lead routing
│   ├── EscalationService.php         # SLA breach escalation
│   └── SLAWatcher.php               # SLA monitoring
├── Domain/
│   ├── Lead.php                      # Lead aggregate root
│   ├── LeadIntent.php                # Intent classification enum
│   ├── LeadScore.php                 # Lead scoring value object
│   ├── LeadState.php                 # Lead lifecycle state machine
│   └── SLA.php                       # SLA definition
├── Infrastructure/
│   ├── LeadAISchema.php              # Database schema
│   ├── Queue.php                     # Lead processing queue
│   ├── EmailProvider.php             # Email delivery
│   └── SMSProvider.php               # SMS delivery
├── UI/AdminDashboard.php             # Lead management UI
└── LeadAIEngine.php                  # Engine bootstrap
```

### Email (`Email/`)

| Class | Description |
|-------|-------------|
| `EmailDigest` | Periodic email digest generation |
| `NewsletterBuilder` | Automated newsletter builder with HTML templates |

### Social (`Social/`)

| Class | Description |
|-------|-------------|
| `SocialPublisher` | Multi-platform social posting (Facebook, Twitter, LinkedIn) |
| `SocialCalendar` | Social content scheduling calendar |
| `PushNotificationPublisher` | Web push notification delivery |

### Distribution (`Distribution/`)

| Class | Description |
|-------|-------------|
| `AMPGenerator` | AMP HTML page generation |
| `RSSFeedBuilder` | Custom RSS/Atom feed builder |

### Scheduler (`Scheduler/`)

| Class | Description |
|-------|-------------|
| `CronManager` | WP-Cron schedule registration (multisite-safe) |
| `PublishScheduler` | Optimal publish time scheduling |
| `TimeZoneScheduler` | Timezone-aware scheduling for global audiences |

### Cache (`Cache/`)

| Class | Description |
|-------|-------------|
| `ContentCache` | Full-page/fragment content caching |
| `ObjectCacheAdapter` | WP Object Cache adapter |
| `CdnManager` | CDN integration (Cloudflare, BunnyCDN) |
| `QueryOptimizer` | Database query optimization and caching |

### Monitoring (`Monitoring/`)

| Class | Description |
|-------|-------------|
| `HealthController` | REST health-check endpoint |
| `AlertManager` | Multi-channel alerting (email, Slack, webhook) |
| `PerformanceDashboard` | Performance metrics dashboard |
| `ErrorTracker` | Error aggregation and tracking |
| `SLAManager` | SLA compliance monitoring |
| `Logger` | Lightweight structured logger |

### Logging (`Logging/`) — PSR-3 Inspired

| Class | Description |
|-------|-------------|
| `LoggerInterface` | Logger contract |
| `AdvancedLogger` | Multi-handler logger |
| `AbstractHandler` | Base log handler |
| `DatabaseHandler` | Log to custom DB table |
| `LegacyLoggerHandler` | Compatibility bridge |
| `ProcessorInterface` | Log record processor contract |
| `MemoryUsageProcessor` | Adds memory stats to log records |
| `RequestContextProcessor` | Adds HTTP request context |
| `WordPressContextProcessor` | Adds WP-specific context (user, site) |

### Integration (`Integration/`)

| Class | Description |
|-------|-------------|
| `PT24Bridge` | PT24 platform bidirectional sync |
| `ZapierManager` | Zapier webhook integration |
| `CTAInjector` | Dynamic CTA block injection |
| `ContentLinker` | Smart content cross-linking |
| `LeadAttributor` | Multi-touch lead attribution |
| `RankingSyncer` | External ranking data sync |

### Keywords (`Keywords/`)

| Class | Description |
|-------|-------------|
| `KeywordCluster` | Immutable pillar + supporting keywords value object |
| `KeywordClusterEngine` | Keyword clustering algorithm |

### Poradnik (`Poradnik/`) — Poradnik.pro Platform

| Class | Description |
|-------|-------------|
| `PoradnikEngine` | Main platform orchestrator |
| `PoradnikAPI` | REST API for poradnik data |
| `DataEngine` | Data aggregation engine |
| `DataScraper` | External data scraping |
| `DecisionEngine` | User decision guidance |
| `ScoringEngine` | Multi-criteria scoring |
| `ABTester` | A/B test framework |
| `AIOptimizer` | AI-powered content optimization |
| `CSVImporter` | Bulk CSV data import |
| `EventTracker` | User event tracking |
| `WorkerManager` | Background worker orchestration |

### Testing (`Testing/`) — A/B Testing Framework

| Class | Description |
|-------|-------------|
| `ABTestEngine` | Core A/B testing engine |
| `AIVariantGenerator` | **v9** — AI-powered headline/CTA variant generation |
| `BayesianOptimizer` | **v9** — Thompson Sampling multi-armed bandit |

### Webhook (`Webhook/`)

| Class | Description |
|-------|-------------|
| `WebhookManager` | Outbound webhook delivery with retry |

### Database (`Database/`)

| Class | Description |
|-------|-------------|
| `PoradnikSchema` | Poradnik V1 DB schema |
| `PoradnikV3Schema` | Poradnik V3 DB schema (categories, calculators, rankings) |
| `PT24IntegrationSchema` | PT24 integration tables |

### CLI (`CLI/`) — WP-CLI Commands

| Command | Class | Description |
|---------|-------|-------------|
| `wp pearblog` | `PearBlogCommand` | Core commands: generate, queue, stats, refresh, quality, links, autopilot, scaffold, audit, topics, import, export, schedule |
| `wp pearblog seo` | `SEOV3Command` | SEO: stats, keywords, generate, verticals, services, modifiers |
| `wp pearblog security` | `SecurityCommand` | Security audit and PII scan |
| `wp pearblog integration` | `IntegrationCommand` | Integration sync commands |
| `wp pearblog v9` | `V9Command` | V9 modules: forecast, collab, mobile, ab, router, orphans, billing, tenant, audit, pii, roi, moderation, rbac, compliance, amp |
| (internal) | `AutopilotRunner` | Autonomous pipeline runner |

---

## REST API Endpoints

| Namespace | Endpoints | Auth |
|-----------|-----------|------|
| `pearblog/v1/automation` | `/create-content`, `/process-content`, `/status` | API key |
| `pearblog/v1/mobile` | `/dashboard`, `/queue`, `/approve`, `/reject`, `/alerts`, `/sites` | ****** |
| `pearblog/v1/graphql` | `/query` | ****** + capability |
| `pearblog/v1/topics` | CRUD | Admin cookie |
| `pearblog/v1/dashboard` | Stats aggregation | Admin cookie |
| `pearblog/v1/roi` | `/`, `/{post_id}` | Admin cookie |
| `pearblog/v1/health` | Health check | Public |
| `pearblog/v1/decision-platform` | Quiz, rankings, comparisons | Mixed |
| `pearblog/v1/lead-ai` | Lead management | Admin cookie |
| `pearblog/v1/search/suggest` | Typeahead | Public |

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
| `pearblog_monetization` | string | Active monetization strategy |
| `pearblog_publish_rate` | int | Articles per pipeline cycle |
| `pearblog_language` | string | Content language ISO 639-1 (en/pl/de) |
| `pearblog_enable_image_generation` | bool | DALL-E 3 image generation toggle |
| `pearblog_image_style` | string | DALL-E 3 style |
| `pearblog_esp_provider` | string | Email provider (none/mailchimp/convertkit) |
| `pearblog_mailchimp_api_key` | string | Mailchimp API key |
| `pearblog_mailchimp_list_id` | string | Mailchimp list/audience ID |
| `pearblog_convertkit_api_key` | string | ConvertKit API key |
| `pearblog_convertkit_form_id` | string | ConvertKit form ID |
| `pearblog_pagespeed_api_key` | string | Google PageSpeed Insights key |
| `pearblog_newsletter_*` | mixed | Newsletter builder configuration |
| `pearblog_push_*` | mixed | Push notification configuration |
| `pearblog_billing_*` | mixed | Billing/quota configuration |

---

## Filters & Actions

| Hook | Type | Description |
|------|------|-------------|
| `pearblog_prompt_builder_class` | Filter | Override the prompt builder class |
| `pearblog_saas_products` | Filter | Modify SaaS product list at runtime |
| `pearblog_airbnb_search_url` | Filter | Customize Airbnb affiliate deep link URL |
| `pearblog_pipeline_started` | Action | Fired when pipeline begins |
| `pearblog_pipeline_completed` | Action | Fired when pipeline finishes (with post_id) |
| `pearblog_site_config` | Filter | Filter all site configuration values |
| `pearblog_async_job_success` | Action | Fired on successful async job execution |
| `pearblog_async_job_failed` | Action | Fired on async job failure |
| `pearblog_lead_captured` | Action | Fired when quiz lead is captured |
| `pearblog_newsletter_sent` | Action | Fired after newsletter delivery |

---

## Security

- **Authentication**: API key + rate limiting (5 req/IP/hour on lead forms)
- **SQL Injection**: All queries via `$wpdb->prepare()` — zero string interpolation
- **XSS**: `wp_kses_post()` on all user-facing output
- **CSRF**: `settings_fields()` + nonce verification on all admin forms
- **Encryption**: AES-256-CBC with HKDF per-tenant key derivation for sensitive options
- **PII**: Automated PII detection and redaction before publication
- **RBAC**: Role-based capability checks on all REST endpoints
- **Secrets**: `.env` and `.env.*` gitignored since v4.3

---

## Further Documentation

| Document | Description |
|----------|-------------|
| [docs/MODULES.md](docs/MODULES.md) | Detailed module architecture and class responsibilities |
| [docs/CLI.md](docs/CLI.md) | Complete WP-CLI command reference |
| [docs/TESTING.md](docs/TESTING.md) | Test architecture, running tests, writing new tests |
| [CHANGELOG.md](../../CHANGELOG.md) | Full release history |
| [API-DOCUMENTATION.md](../../API-DOCUMENTATION.md) | REST API reference with examples |

---

*PearBlog Engine v9.0 — Namespace `PearBlogEngine` — 224 classes · 33 modules · 1 760 tests*
