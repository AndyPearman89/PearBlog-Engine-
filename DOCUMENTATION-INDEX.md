# 📚 PearBlog Engine — Documentation Index

> Autonomous AI content production for WordPress — **v6.0**

---

## 🚀 Quick Start

1. **[README.md](README.md)** — Project overview and architecture
2. **[SETUP.md](SETUP.md)** — Installation & configuration (5 min)
3. **[AUTONOMOUS-ACTIVATION-GUIDE.md](AUTONOMOUS-ACTIVATION-GUIDE.md)** — Launch autonomous production

---

## 📖 Documentation Map

| Document | Language | Audience | Summary |
|----------|----------|----------|---------|
| [README.md](README.md) | EN | Everyone | Features, architecture, quick start |
| [SETUP.md](SETUP.md) | EN | Ops / Dev | GitHub Secrets, Actions, first run |
| [DEPLOYMENT.md](DEPLOYMENT.md) | EN | Ops / Dev | Full production deployment guide (Apache/Nginx, SSL, Git, FTP, WP-CLI, 4 hosting providers) |
| [DEPLOYMENT-PL.md](DEPLOYMENT-PL.md) | **PL** | Ops / Dev | Pełny przewodnik wdrożenia produkcyjnego po polsku |
| [DATABASE-MIGRATIONS.md](DATABASE-MIGRATIONS.md) | EN | Ops / Dev | Full schema reference, upgrade SQL scripts v4→v5→v6, rollback procedures, compatibility matrix |
| [DISASTER-RECOVERY.md](DISASTER-RECOVERY.md) | EN | Ops / DevOps | Disaster recovery plan: RTO/RPO targets, backup automation, 8 disaster scenarios, restore procedures |
| [PROGRESS-VISUALIZATION.md](PROGRESS-VISUALIZATION.md) | **PL / EN** | Everyone | Bilingual progress visualization: milestones, timeline, metrics (PL + EN) |
| [AUTONOMOUS-ACTIVATION-GUIDE.md](AUTONOMOUS-ACTIVATION-GUIDE.md) | PL | Ops | Step-by-step autonomous activation |
| [PRODUCTION-ANALYSIS-FULL.md](PRODUCTION-ANALYSIS-FULL.md) | PL | Ops / Dev | Complete production operations manual |
| [BUSINESS-STRATEGY.md](BUSINESS-STRATEGY.md) | PL / EN | Business | ROI, monetization, scaling |
| [MARKETING-GUIDE.md](MARKETING-GUIDE.md) | EN | Marketing | SEO, traffic, affiliate strategy |
| [TRAVEL-CONTENT-ENGINE.md](TRAVEL-CONTENT-ENGINE.md) | EN | Dev | Specialized travel prompt builders |
| [CHANGELOG.md](CHANGELOG.md) | EN | Everyone | Release history |
| [ENTERPRISE-AUTOPILOT-TASKLIST.md](ENTERPRISE-AUTOPILOT-TASKLIST.md) | EN | Dev / Ops | 23-task autonomous execution plan (Phase 1-7) |
| [PRODUCTION-CHECKLIST.md](PRODUCTION-CHECKLIST.md) | EN | Ops | Standalone pre-launch & weekly/monthly/quarterly operations checklists |
| [ROADMAP-VISUAL.md](ROADMAP-VISUAL.md) | EN | Everyone | Visual roadmap, milestones, timeline v6.0→v7.0 |

### Theme Documentation

| Document | Summary |
|----------|---------|
| [theme/pearblog-theme/README.md](theme/pearblog-theme/README.md) | Theme v5.1 features, layout, new templates, customization |

### Plugin & Scripts

| Document | Summary |
|----------|---------|
| [mu-plugins/pearblog-engine/README.md](mu-plugins/pearblog-engine/README.md) | Plugin v5.1 architecture, 8-step pipeline, all modules, REST API |
| [scripts/README.md](scripts/README.md) | Python automation suite, environment variables, usage |

### Brand Assets

| Document | Summary |
|----------|---------|
| [brand-assets/README.md](brand-assets/README.md) | Brand asset index |
| [brand-assets/PEARBLOG_ULTRA_PRO_BRAND_SYSTEM.md](brand-assets/PEARBLOG_ULTRA_PRO_BRAND_SYSTEM.md) | Brand identity guidelines |

---

## 🎯 I Want To…

| Goal | Start Here |
|------|------------|
| Get started quickly | [SETUP.md](SETUP.md) → [AUTONOMOUS-ACTIVATION-GUIDE.md](AUTONOMOUS-ACTIVATION-GUIDE.md) |
| Deploy to production (EN) | [DEPLOYMENT.md](DEPLOYMENT.md) |
| Deploy to production (PL) | [DEPLOYMENT-PL.md](DEPLOYMENT-PL.md) |
| Track project progress | [PROGRESS-VISUALIZATION.md](PROGRESS-VISUALIZATION.md) |
| Understand how it works | [README.md](README.md) → [PRODUCTION-ANALYSIS-FULL.md](PRODUCTION-ANALYSIS-FULL.md) |
| Make money from this | [BUSINESS-STRATEGY.md](BUSINESS-STRATEGY.md) → [MARKETING-GUIDE.md](MARKETING-GUIDE.md) |
| Understand the plugin architecture | [mu-plugins/pearblog-engine/README.md](mu-plugins/pearblog-engine/README.md) |
| Run Python automation scripts | [scripts/README.md](scripts/README.md) |
| Create travel content | [TRAVEL-CONTENT-ENGINE.md](TRAVEL-CONTENT-ENGINE.md) |
| Scale to multiple sites | [PRODUCTION-ANALYSIS-FULL.md](PRODUCTION-ANALYSIS-FULL.md) § 7 |
| Optimize costs | [PRODUCTION-ANALYSIS-FULL.md](PRODUCTION-ANALYSIS-FULL.md) § 9 |
| Troubleshoot issues | [DEPLOYMENT.md](DEPLOYMENT.md#10-troubleshooting) · [DATABASE-MIGRATIONS.md](DATABASE-MIGRATIONS.md#10-troubleshooting) |
| See enterprise roadmap | [ROADMAP-VISUAL.md](ROADMAP-VISUAL.md) |
| Run autopilot tasks | [ENTERPRISE-AUTOPILOT-TASKLIST.md](ENTERPRISE-AUTOPILOT-TASKLIST.md) |
| Verify production readiness | [PRODUCTION-CHECKLIST.md](PRODUCTION-CHECKLIST.md) |

---

## 🔧 Architecture Overview — v6.0

```
PearBlog Engine v6.0
├── mu-plugins/pearblog-engine/     # Core WordPress MU-plugin
│   ├── src/Pipeline/               # 8-step autonomous content pipeline
│   ├── src/AI/                     # GPT-4o-mini + DALL-E 3 + ImageAnalyzer
│   ├── src/Content/                # 4 prompt builders + validator + queue
│   ├── src/SEO/                    # SEOEngine + ProgrammaticSEO (Schema, OG, audit)
│   ├── src/Monetization/           # AdSense + Affiliate + SaaS CTA injection
│   ├── src/Scheduler/              # WP-Cron management (multisite-safe)
│   ├── src/Keywords/               # Keyword clustering value object
│   ├── src/API/                    # REST automation endpoints
│   ├── src/Admin/                  # Top-level WP admin menu (6 tabs) + DashboardWidget
│   ├── src/Tenant/                 # Multi-site context
│   └── assets/css/admin.css        # Admin panel styles
│
├── theme/pearblog-theme/           # SEO-first WordPress theme v5.1
│   ├── index.php                   # Homepage with hero + card grid
│   ├── single.php                  # 12-element SEO article layout
│   ├── page.php                    # Static page template (NEW v5.1)
│   ├── search.php                  # Search results (NEW v5.1)
│   ├── 404.php                     # Error page (NEW v5.1)
│   ├── category.php                # Category archive
│   ├── inc/                        # 17 modules (monetization, analytics, layout, …)
│   ├── template-parts/             # 13 reusable block templates
│   └── assets/
│       ├── css/                    # base, components, utilities
│       └── js/                     # app.js, lazyload.js, personalization.js
│
└── scripts/                        # Python automation (optional)
    ├── automation_orchestrator.py   # Full-cycle orchestration
    ├── keyword_engine.py           # Keyword research & clustering
    ├── scraping_engine.py          # SERP & competitor data extraction
    ├── serp_analyzer.py            # Competition analysis
    └── run_pipeline.py             # Pipeline execution via GitHub Actions
```

### Pipeline Flow (Hourly via WP-Cron) — 8 Steps

```
Topic Queue
  Step 1 → PromptBuilderFactory (selects builder by industry/niche)
  Step 2 → GPT-4o-mini content generation
  Step 3 → Draft post created (WordPress)
  Step 4 → SEOEngine (title, meta, Schema.org, Open Graph)
  Step 5 → MonetizationEngine (ad slots + affiliate + SaaS CTA)
  Step 6 → ImageGenerator (DALL-E 3 featured image)
  Step 6b→ ProgrammaticSEO auto-generates meta description fallback
  Step 7 → Post published

~55 sec / article · $0.08 / article (with image)
```

---

## 📊 Key Metrics

| Metric | Value |
|--------|-------|
| Cost per article (with image) | $0.08 |
| Cost per article (text only) | $0.0003 |
| Articles per month (publish_rate=1) | 720 |
| Monthly cost (720 articles + images) | ~$58 |
| Break-even traffic | ~5,000 visitors/month |
| Pipeline execution time | ~55 seconds |
| Automation level | 100% |

---

## 🆘 Troubleshooting

| Problem | Reference |
|---------|-----------|
| Cron not running | [PRODUCTION-ANALYSIS-FULL.md](PRODUCTION-ANALYSIS-FULL.md) § 8.1 |
| No images generated | [PRODUCTION-ANALYSIS-FULL.md](PRODUCTION-ANALYSIS-FULL.md) § 8.2 |
| High API costs | [PRODUCTION-ANALYSIS-FULL.md](PRODUCTION-ANALYSIS-FULL.md) § 8.3 |
| Low content quality | [PRODUCTION-ANALYSIS-FULL.md](PRODUCTION-ANALYSIS-FULL.md) § 8.4 |
| Search panel not working | See `assets/js/app.js` → `initSearchPanel()` |
| Dark mode not persisting | `localStorage` key `pb_dark_mode` — check browser storage |

---

*PearBlog Engine v6.0 — Enterprise-ready autonomous content system*
