# ⚙️ PearBlog Engine — WordPress MU-Plugin v8.0

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
pearblog-engine.php              # Bootstrap & PSR-4 autoloader (version 8.0.0)
assets/
└── css/admin.css                # Admin panel styles
src/
├── Core/Plugin.php              # Singleton boot — registers all sub-systems
├── Pipeline/                    # 12-step autonomous content generation
│   ├── ContentPipeline.php      # Main pipeline orchestrator
│   ├── BackgroundProcessor.php  # Async WP-Cron queue processing
│   ├── ContentImportExport.php  # Bulk topic import (CSV/JSON) + article export
│   ├── PipelineAuditLog.php     # Ring-buffer audit log with REST read/append
│   ├── ApprovalWorkflow.php     # Content approval workflow
│   └── AsyncQueueManager.php   # Async queue management
├── AI/                          # Multi-provider AI client layer
│   ├── AIClient.php             # Provider-agnostic chat client (circuit breaker, cost tracking)
│   ├── AIProviderInterface.php  # Provider contract
│   ├── AIProviderFactory.php    # Factory: OpenAI / Anthropic / Gemini
│   ├── OpenAIProvider.php       # OpenAI GPT-4o-mini + DALL-E 3
│   ├── AnthropicProvider.php    # Anthropic Claude adapter
│   ├── GeminiProvider.php       # Google Gemini adapter
│   ├── ImageGenerator.php       # DALL-E 3 image generation (4 styles)
│   ├── ImageAnalyzer.php        # Media library audit + keyword suggestions
│   ├── ContentRewriter.php      # AI-powered content rewriting
│   ├── FactChecker.php          # AI fact verification
│   ├── PromptOptimizer.php      # Dynamic prompt optimization
│   ├── VideoScriptBuilder.php   # Video script generation
│   ├── PodcastGenerator.php     # Podcast script generation
│   ├── StreamingAIClient.php    # Streaming response client
│   └── RateLimitException.php   # Rate limit error
├── Content/                     # 28+ content builders and processors
│   ├── PromptBuilder.php        # Base prompt builder (SEO + monetization rules)
│   ├── PromptBuilderFactory.php # Auto-selects builder by industry keywords
│   ├── TravelPromptBuilder.php  # Travel content with mandatory sections
│   ├── BeskidyPromptBuilder.php # Beskidy mountains (weather + day planner)
│   ├── MultiLanguageTravelBuilder.php # PL/EN/DE localization
│   ├── PT24PromptBuilder.php    # PT24 service landing pages
│   ├── PoradnikPromptBuilder.php# Poradnik.pro decision content
│   ├── EcommercePromptBuilder.php # E-commerce content
│   ├── FinancePromptBuilder.php # Finance/banking content
│   ├── FoodPromptBuilder.php    # Food & recipe content
│   ├── HealthPromptBuilder.php  # Health & wellness content
│   ├── GlossaryBuilder.php      # Glossary / definition pages
│   ├── PoradnikCostTemplateBuilder.php # Pricing/cost template
│   ├── PoradnikV3TemplateBuilder.php   # Poradnik V3 template engine
│   ├── ContentValidator.php     # Structure & quality validation (3 modes)
│   ├── DuplicateDetector.php    # Similarity check (blocks if ≥ 80%)
│   ├── QualityScorer.php        # 0–100 content quality score
│   ├── ReadabilityAnalyzer.php  # Readability metrics
│   ├── ContentRefreshEngine.php # Content freshness scoring & refresh queue
│   ├── ContentScore.php         # Composite content score value object
│   ├── FewShotEngine.php        # Few-shot prompt examples engine
│   ├── PersonaBuilder.php       # Audience persona generation
│   ├── CompetitiveGapEngine.php # SERP gap analysis
│   ├── MultilingualManager.php  # Runtime language switching
│   ├── LivePricingDataLayer.php # Live pricing data integration
│   ├── PostMetaManager.php      # Post meta read/write helper
│   ├── CTABlockCPT.php          # CTA custom post type
│   ├── FAQBlockCPT.php          # FAQ custom post type
│   └── SerpScraper.php          # SERP data extraction
├── Analytics/                   # GA4 + advanced analytics
│   ├── GA4Client.php            # GA4 Data API client (daily sync)
│   ├── AnalyticsDashboard.php   # Dashboard widget + REST analytics
│   ├── SearchIntentEngine.php   # Search intent classification
│   ├── PredictiveEngine.php     # Predictive analytics
│   ├── CohortEngine.php         # User cohort analysis
│   ├── ContentROIEngine.php     # Content ROI calculation
│   ├── ConversionFlowTracker.php# Conversion funnel tracking
│   └── ConversionTracker.php    # Individual conversion events
├── SEO/                         # Full SEO automation suite
│   ├── SEOEngine.php            # Meta tags, Schema.org injection
│   ├── ProgrammaticSEO.php      # Schema.org JSON-LD, OG, Twitter Cards, bulk audit
│   ├── SchemaManager.php        # Advanced Schema.org types
│   ├── InternalLinker.php       # Keyword-based internal link injection
│   ├── ProgrammaticLocalSEO.php # Local SEO (city/service pages)
│   ├── TopicalAuthorityEngine.php # Topical authority & pillar clusters
│   ├── HreflangManager.php      # Hreflang tags for multilingual sites
│   ├── XmlSitemapManager.php    # XML sitemap generation
│   ├── SearchConsoleClient.php  # Google Search Console API client
│   ├── KeywordDatabase.php      # Keyword database (v1)
│   ├── KeywordDatabaseV3.php    # Keyword database (v3 — enterprise)
│   ├── KeywordGeneratorCLI.php  # CLI keyword generation
│   └── KeywordGeneratorV3CLI.php# CLI keyword generation v3
├── Monetization/MonetizationEngine.php # AdSense + Affiliate + SaaS CTA
├── Scheduler/                   # WP-Cron management
│   ├── CronManager.php          # WP-Cron hook registration (multisite-safe)
│   ├── PublishScheduler.php     # GA4-based optimal publish timing
│   └── TimeZoneScheduler.php    # Time-zone-aware scheduling
├── Keywords/                    # Keyword clustering
│   ├── KeywordCluster.php       # Immutable value object (pillar + supporting keywords)
│   └── KeywordClusterEngine.php # IDF-based GA4 keyword clustering
├── Cache/                       # Cache & CDN layer
│   ├── ContentCache.php         # Content fragment cache
│   ├── ObjectCacheAdapter.php   # wp_cache_* adapter
│   ├── CdnManager.php           # BunnyCDN + Cloudflare offloading
│   └── QueryOptimizer.php       # DB query optimization
├── API/                         # REST endpoints
│   ├── AutomationController.php # Core automation endpoints
│   ├── DashboardController.php  # Dashboard data REST API
│   ├── TopicsController.php     # Topics queue REST API
│   ├── GraphQLController.php    # GraphQL endpoint
│   ├── PermissionManager.php    # RBAC + API key auth
│   ├── RateLimiter.php          # Request rate limiting
│   ├── PoradnikV3API.php        # Poradnik V3 REST API
│   └── SearchSuggestAPI.php     # Search suggestions REST API
├── Admin/                       # WP Admin interface (20 classes)
│   ├── AdminPageV8Enterprise.php# Enterprise 15-tab admin dashboard (v8.0)
│   ├── AdminPageV7.php          # V7 admin page
│   ├── AdminPage.php            # Legacy admin page (6 tabs)
│   ├── DashboardWidget.php      # WP Dashboard pipeline stats widget
│   ├── OnboardingWizardV2.php   # Step-by-step onboarding (v2)
│   ├── OnboardingWizard.php     # Step-by-step onboarding
│   ├── ContentCalendar.php      # Visual content calendar
│   ├── WhiteLabelManager.php    # White-label customization
│   ├── DatabaseMigration.php    # DB migration runner
│   └── [13 tab controllers]     # StrategyTab, ContentEngineTab, SEOTab, MonetizationTab, LeadsTab, AutomationTab, AnalyticsTab, MultisiteTab, PerformanceDashboardTab, SettingsTab, DashboardTab…
├── Monitoring/                  # Observability & alerting
│   ├── AlertManager.php         # Slack/Discord/email alerting
│   ├── HealthController.php     # System health check REST endpoint
│   ├── PerformanceDashboard.php # Performance metrics dashboard
│   ├── Logger.php               # Structured logger
│   ├── SLAManager.php           # SLA tracking & reporting
│   └── ErrorTracker.php         # Error aggregation
├── Social/                      # Social media & notifications
│   ├── SocialPublisher.php      # Auto-publish to social platforms
│   ├── SocialCalendar.php       # Social publishing calendar
│   └── PushNotificationPublisher.php # Web push notifications
├── CLI/                         # WP-CLI commands
│   ├── PearBlogCommand.php      # Main `wp pearblog` command
│   ├── AutopilotRunner.php      # 26-task autopilot (7 phases)
│   ├── IntegrationCommand.php   # Integration management CLI
│   ├── SEOV3Command.php         # SEO v3 CLI commands
│   └── SecurityCommand.php      # Security CLI commands
├── Tenant/                      # Multi-site context
│   ├── TenantContext.php        # Runtime context (domain + profile)
│   └── SiteProfile.php          # Value object: niche + strategy
├── LeadAI/                      # PT24 AI Lead Engine V2 (DDD)
│   ├── Domain/                  # Entities, value objects, domain events
│   ├── Application/             # Use cases, services
│   ├── Infrastructure/          # Persistence, external integrations
│   ├── UI/                      # REST controllers, admin views
│   └── LeadAIEngine.php         # Engine bootstrap
├── Poradnik/                    # Poradnik.pro content engine
│   ├── PoradnikEngine.php       # Main engine bootstrap
│   ├── AIOptimizer.php          # AI content optimization
│   ├── ScoringEngine.php        # Content scoring
│   ├── DecisionEngine.php       # Decision-tree content logic
│   ├── DataEngine.php           # Data aggregation
│   ├── EventTracker.php         # Event tracking
│   ├── WorkerManager.php        # Background worker management
│   ├── CSVImporter.php          # CSV data import
│   ├── DataScraper.php          # Data scraping
│   └── PoradnikAPI.php          # REST API
├── Security/                    # Security & compliance
│   ├── RBACManager.php          # Role-based access control
│   ├── SecurityAuditor.php      # Security audit & reporting
│   ├── ContentModerator.php     # AI content moderation
│   ├── PIIDetector.php          # PII detection & masking
│   └── ComplianceExporter.php   # GDPR/compliance export
├── DecisionPlatform/            # Full Decision Platform (Poradnik.pro)
│   ├── DecisionPlatformManager.php # Platform bootstrap
│   ├── DecisionPlatformAPI.php  # REST API
│   ├── ComparisonEngine.php     # AI-generated comparisons (A vs B)
│   ├── RankingEngine.php        # TOP rankings with local filtering
│   ├── Calculator.php           # Interactive cost/ROI calculators
│   ├── DecisionAssistant.php    # AI personalized recommendations
│   ├── IntentDetector.php       # User intent detection
│   ├── LinkGraph.php            # Internal link relationship graph
│   ├── LeadGenerator.php        # Lead capture + matching
│   ├── QuizEngine.php           # Decision quiz engine
│   ├── PriceComparison.php      # Price comparison tool
│   ├── BlockRenderer.php        # Gutenberg block renderer
│   └── [value objects]          # Article, Comparison, Expert, Offer, Ranking
├── Distribution/                # Content distribution
│   ├── AMPGenerator.php         # AMP page generation
│   └── RSSFeedBuilder.php       # Custom RSS feed builder
├── Email/                       # Email marketing
│   ├── EmailDigest.php          # Automated email digest
│   └── NewsletterBuilder.php    # Newsletter content builder
├── Integration/                 # External integrations
│   ├── PT24Bridge.php           # PT24 platform bridge
│   ├── ZapierManager.php        # Zapier webhook integration
│   ├── CTAInjector.php          # Dynamic CTA injection
│   ├── ContentLinker.php        # Cross-content linking
│   ├── LeadAttributor.php       # Lead source attribution
│   └── RankingSyncer.php        # External ranking data sync
├── Logging/                     # Advanced structured logging
│   ├── AdvancedLogger.php       # PSR-3 compatible advanced logger
│   ├── DatabaseHandler.php      # DB log handler
│   ├── AbstractHandler.php      # Base handler
│   ├── LoggerInterface.php      # Logger interface
│   ├── ProcessorInterface.php   # Processor interface
│   ├── RequestContextProcessor.php  # HTTP request context
│   ├── WordPressContextProcessor.php # WP context enrichment
│   ├── MemoryUsageProcessor.php # Memory usage tracking
│   └── LegacyLoggerHandler.php  # Legacy logger compatibility
├── Database/                    # Schema management
│   ├── PT24IntegrationSchema.php# PT24 DB schema (9 tables)
│   ├── PoradnikSchema.php       # Poradnik DB schema
│   └── PoradnikV3Schema.php     # Poradnik V3 DB schema
├── Webhook/WebhookManager.php   # Outgoing webhook management
└── Testing/ABTestEngine.php     # A/B test engine
```

---

## Pipeline — 12 Steps

Each call to `ContentPipeline::run()` processes exactly one article (next topic from the queue):

| Step | Action | Class |
|------|--------|-------|
| 1 | Pop topic from queue | `TopicQueue::pop()` |
| 2 | Build prompt | `PromptBuilderFactory::create()` + `build()` |
| 3 | Generate content | `AIClient::generate()` (GPT-4o-mini) |
| 4 | Duplicate check | `DuplicateDetector::is_duplicate()` (blocks if similarity ≥ 80%) |
| 5 | Create draft post | `wp_insert_post()` |
| 6 | Apply SEO metadata | `SEOEngine::apply()` |
| 7 | Inject monetization | `MonetizationEngine::apply()` |
| 8 | Inject internal links | `InternalLinker::inject()` (up to 5 links) |
| 9 | Generate featured image | `ImageGenerator::generate_and_attach()` (DALL-E 3) |
| 10 | Update duplicate index | `DuplicateDetector::index()` |
| 11 | Publish post | `wp_update_post()` with `post_status=publish` |
| 12 | Score + alert | `QualityScorer::score()` + `AlertManager::notify()` |

Cost: **~$0.08 / article** · Time: **~55 seconds**

---

## Modules

### Core (`Core/Plugin.php`)

Singleton that boots all sub-systems exactly once via `Plugin::boot()`.
Registers all controllers, CLI commands, REST routes, cron schedules, and admin pages.

### AI (`AI/`)

| Class | Description |
|-------|-------------|
| `AIClient` | Provider-agnostic chat client with circuit breaker and cost tracking |
| `AIProviderFactory` | Selects provider (OpenAI / Anthropic / Gemini) at runtime |
| `OpenAIProvider` | OpenAI GPT-4o-mini Chat Completions |
| `AnthropicProvider` | Anthropic Claude adapter |
| `GeminiProvider` | Google Gemini adapter |
| `ImageGenerator` | DALL-E 3 image generation — 4 styles (photorealistic, illustration, artistic, minimal); uploads to media library and sets featured image |
| `ImageAnalyzer` | Media library audit: missing alt texts, posts without featured images, AI-generated image tracking, oversized image detection |
| `ContentRewriter` | AI-powered content rewriting and improvement |
| `FactChecker` | AI-backed fact verification |
| `PromptOptimizer` | Dynamic prompt optimization based on performance signals |
| `VideoScriptBuilder` | Video script generation from article content |
| `PodcastGenerator` | Podcast episode script generation |
| `StreamingAIClient` | Streaming response client for real-time output |

### Content (`Content/`)

| Class | Description |
|-------|-------------|
| `PromptBuilder` | Base prompt builder with SEO and monetization rules |
| `PromptBuilderFactory` | Auto-selects builder based on industry keywords |
| `TravelPromptBuilder` | Travel content with mandatory sections (extends PromptBuilder) |
| `BeskidyPromptBuilder` | Beskidy mountains — weather and day planner (extends TravelPromptBuilder) |
| `MultiLanguageTravelBuilder` | PL/EN/DE localization (extends BeskidyPromptBuilder) |
| `PT24PromptBuilder` | PT24 service landing page builder |
| `PoradnikPromptBuilder` | Poradnik.pro decision-content builder |
| `EcommercePromptBuilder` | E-commerce product/category content |
| `FinancePromptBuilder` | Finance and banking content |
| `FoodPromptBuilder` | Food and recipe content |
| `HealthPromptBuilder` | Health and wellness content |
| `GlossaryBuilder` | Glossary / definition page builder |
| `ContentValidator` | Validates structure and quality in 3 modes (generic/travel/beskidy) |
| `DuplicateDetector` | Similarity check — blocks articles with ≥ 80% cosine similarity |
| `QualityScorer` | 0–100 composite quality score saved as post meta |
| `ReadabilityAnalyzer` | Flesch–Kincaid and custom readability metrics |
| `ContentRefreshEngine` | Freshness scoring and refresh-queue management |
| `FewShotEngine` | Few-shot prompt examples from high-performing articles |
| `PersonaBuilder` | Audience persona generation for targeted prompts |
| `CompetitiveGapEngine` | SERP gap analysis — identifies content opportunities |
| `MultilingualManager` | Runtime language switching across content builders |
| `TopicQueue` | FIFO topic queue persisted as WordPress option per site |

**Builder selection** (via `PromptBuilderFactory`):
- Beskidy keywords → `MultiLanguageTravelBuilder`
- Travel keywords → `TravelPromptBuilder`
- PT24 keywords → `PT24PromptBuilder`
- Poradnik keywords → `PoradnikPromptBuilder`
- Everything else → `PromptBuilder`
- Override via `pearblog_prompt_builder_class` filter.

### SEO (`SEO/`)

| Class | Description |
|-------|-------------|
| `SEOEngine` | Parses AI content, extracts title/meta/Schema.org, stores post meta |
| `ProgrammaticSEO` | Schema.org JSON-LD (Article, BreadcrumbList, FAQPage), Open Graph, Twitter Cards, keyword density analysis, bulk SEO audit, internal link suggestions, meta description auto-generator |
| `SchemaManager` | Advanced Schema.org type management |
| `InternalLinker` | Keyword-based automatic internal link injection (up to 5 per article) |
| `ProgrammaticLocalSEO` | Local SEO — city/service page generation |
| `TopicalAuthorityEngine` | Topical authority scoring and pillar-cluster management |
| `HreflangManager` | Hreflang tag management for multilingual sites |
| `XmlSitemapManager` | XML sitemap generation and submission |
| `SearchConsoleClient` | Google Search Console API integration |
| `KeywordDatabase` / `KeywordDatabaseV3` | Keyword database (v1 and v3 enterprise editions) |

### Monetization (`Monetization/MonetizationEngine.php`)

Three monetization layers injected into content:
- **v1 — AdSense**: Ad slots at configurable positions
- **v2 — Affiliate**: Booking.com + Airbnb deep links
- **v3 — SaaS CTA**: Keyword-matched product recommendations

Configurable via WP Admin → PearBlog Engine Enterprise → Monetization tab, or `pearblog_saas_products` filter.

### Scheduler (`Scheduler/`)

| Class | Description |
|-------|-------------|
| `CronManager` | Registers custom WP-Cron schedules; multisite-safe via `switch_to_blog()` / `restore_current_blog()` |
| `PublishScheduler` | Analyses GA4 engagement data to find the optimal publish hour/day; falls back to configurable defaults (Tuesday 10:00) |
| `TimeZoneScheduler` | Time-zone-aware scheduling for distributed deployments |

### Keywords (`Keywords/`)

| Class | Description |
|-------|-------------|
| `KeywordCluster` | Immutable value object: pillar keyword + supporting keywords |
| `KeywordClusterEngine` | IDF-based GA4 keyword clustering — groups organic search terms into topical clusters |

### Cache (`Cache/`)

| Class | Description |
|-------|-------------|
| `ContentCache` | Content fragment caching with TTL |
| `ObjectCacheAdapter` | Thin `wp_cache_*` adapter with typed helpers |
| `CdnManager` | BunnyCDN + Cloudflare asset offloading and cache purging |
| `QueryOptimizer` | DB query optimization and caching strategies |

### API (`API/`)

REST namespace: `pearblog/v1/`

| Endpoint group | Controller | Auth |
|---------------|------------|------|
| `/automation/*` | `AutomationController` | API key / admin |
| `/dashboard/*` | `DashboardController` | admin |
| `/topics/*` | `TopicsController` | admin |
| `/graphql` | `GraphQLController` | API key / admin |
| `/poradnik-v3/*` | `PoradnikV3API` | admin |
| `/search-suggest` | `SearchSuggestAPI` | public |

### Admin (`Admin/`) — v8.0 Enterprise

| Class | Description |
|-------|-------------|
| `AdminPageV8Enterprise` | 15-tab enterprise dashboard: Strategy, Content Engine, SEO, Monetization, Leads, Automation, Analytics, Multisite, Performance Dashboard, Settings, and 5 custom tabs |
| `AdminPageV7` | V7 admin page (legacy) |
| `AdminPage` | Original 6-tab admin page (legacy) |
| `DashboardWidget` | WP Dashboard pipeline stats widget |
| `OnboardingWizardV2` | Guided setup wizard v2 |
| `ContentCalendar` | Visual content publishing calendar |
| `WhiteLabelManager` | White-label customization (logo, colors, branding) |

### Monitoring (`Monitoring/`)

| Class | Description |
|-------|-------------|
| `AlertManager` | Slack/Discord/email alerting with configurable thresholds |
| `HealthController` | REST health check (`GET /pearblog/v1/health`) |
| `PerformanceDashboard` | Pipeline and system performance metrics |
| `Logger` | Structured logger (PSR-3) |
| `SLAManager` | SLA target tracking and breach reporting |
| `ErrorTracker` | Error aggregation and trend analysis |

### Security (`Security/`)

| Class | Description |
|-------|-------------|
| `RBACManager` | Role-based access control for all plugin features |
| `SecurityAuditor` | Security audit reports and recommendations |
| `ContentModerator` | AI-powered content moderation |
| `PIIDetector` | Detects and masks personally identifiable information |
| `ComplianceExporter` | GDPR / privacy compliance data export |

### Decision Platform (`DecisionPlatform/`)

Full decision platform for Poradnik.pro — transforms content into an enterprise decision engine:

| Component | Description |
|-----------|-------------|
| `ComparisonEngine` | AI-generated comparison pages (Product A vs B) |
| `RankingEngine` | TOP lists with local filtering (Best X in {city}) |
| `Calculator` | Interactive cost/ROI calculators |
| `DecisionAssistant` | AI-powered personalized recommendations |
| `IntentDetector` | Automatic content enrichment based on user intent |
| `LinkGraph` | Internal link relationship graph auto-discovery |
| `LeadGenerator` | Lead capture, scoring, and matching |
| `QuizEngine` | Decision quiz / product selector engine |
| `PriceComparison` | Real-time price comparison tool |

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

*PearBlog Engine v8.0 — Namespace `PearBlogEngine`*
