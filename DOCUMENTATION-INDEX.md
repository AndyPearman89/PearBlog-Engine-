# 📚 PearBlog Engine — Documentation Index

> Autonomous AI content production for WordPress.

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
| [AUTONOMOUS-ACTIVATION-GUIDE.md](AUTONOMOUS-ACTIVATION-GUIDE.md) | PL | Ops | Step-by-step autonomous activation |
| [PRODUCTION-ANALYSIS-FULL.md](PRODUCTION-ANALYSIS-FULL.md) | PL | Ops / Dev | Complete production operations manual |
| [BUSINESS-STRATEGY.md](BUSINESS-STRATEGY.md) | PL / EN | Business | ROI, monetization, scaling |
| [MARKETING-GUIDE.md](MARKETING-GUIDE.md) | EN | Marketing | SEO, traffic, affiliate strategy |
| [TRAVEL-CONTENT-ENGINE.md](TRAVEL-CONTENT-ENGINE.md) | EN | Dev | Specialized travel prompt builders |
| [CHANGELOG.md](CHANGELOG.md) | EN | Everyone | Release history |

### Theme Documentation

| Document | Summary |
|----------|---------|
| [theme/pearblog-theme/README.md](theme/pearblog-theme/README.md) | Theme features, layout, customization |

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
| Understand how it works | [README.md](README.md) → [PRODUCTION-ANALYSIS-FULL.md](PRODUCTION-ANALYSIS-FULL.md) |
| Make money from this | [BUSINESS-STRATEGY.md](BUSINESS-STRATEGY.md) → [MARKETING-GUIDE.md](MARKETING-GUIDE.md) |
| Create travel content | [TRAVEL-CONTENT-ENGINE.md](TRAVEL-CONTENT-ENGINE.md) |
| Scale to multiple sites | [PRODUCTION-ANALYSIS-FULL.md](PRODUCTION-ANALYSIS-FULL.md) § 7 |
| Optimize costs | [PRODUCTION-ANALYSIS-FULL.md](PRODUCTION-ANALYSIS-FULL.md) § 9 |
| Troubleshoot issues | [PRODUCTION-ANALYSIS-FULL.md](PRODUCTION-ANALYSIS-FULL.md) § 8 |

---

## 🔧 Architecture Overview

```
PearBlog Engine v4.2
├── mu-plugins/pearblog-engine/     # Core WordPress MU-plugin
│   ├── src/Pipeline/               # 7-step autonomous content pipeline
│   ├── src/AI/                     # GPT-4o-mini + DALL-E 3 integration
│   ├── src/Content/                # 4 prompt builders + validator + scorer
│   ├── src/SEO/                    # SEO metadata engine
│   ├── src/Monetization/           # AdSense + SaaS CTA injection
│   ├── src/Scheduler/              # WP-Cron management
│   ├── src/Keywords/               # Keyword clustering
│   ├── src/API/                    # REST automation endpoints
│   ├── src/Admin/                  # WordPress admin page
│   └── src/Tenant/                 # Multi-site context
│
├── theme/pearblog-theme/           # SEO-first WordPress theme
│   ├── inc/                        # Monetization, analytics, lead gen, customizer
│   ├── template-parts/             # Reusable blocks (affiliate, ads, FAQ, TOC, TL;DR)
│   └── assets/                     # CSS + JS
│
└── scripts/                        # Python automation (optional)
    ├── automation_orchestrator.py   # Full-cycle orchestration
    ├── keyword_engine.py           # Keyword research & clustering
    ├── scraping_engine.py          # SERP & competitor data extraction
    ├── serp_analyzer.py            # Competition analysis
    └── run_pipeline.py             # Pipeline execution via GitHub Actions
```

### Pipeline Flow (Hourly via WP-Cron)

```
Topic Queue → PromptBuilder (Factory) → GPT-4o-mini → SEO Engine
  → MonetizationEngine → DALL-E 3 Image → Publish

~55 sec / article • $0.08 / article (with image)
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

---

*PearBlog Engine v4.2 — Built for systematic content entrepreneurs*
