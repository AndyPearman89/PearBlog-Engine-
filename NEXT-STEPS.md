# 🚀 NEXT STEPS — PearBlog Engine v7.0

> **Current state:** v6.0 complete (218 tests · 440 assertions · 25+ docs · all milestones ✅)  
> **Goal:** Public launch v7.0 — Target: **2026-05-10**  
> **Owner:** @AndyPearman89

---

## ✅ What's Done (v6.0)

All 26 enterprise tasks across 7 phases are complete:

| Phase | Status |
|-------|--------|
| Phase 1 — Production Hardening | ✅ Complete |
| Phase 2 — Testing Expansion | ✅ Complete |
| Phase 3 — Monitoring & Operations | ✅ Complete |
| Phase 4 — Documentation & UX | ✅ Complete |
| Phase 5 — Advanced Features | ✅ Complete |
| Phase 6 — Polish & Optimization | ✅ Complete |
| Phase 7 — Launch Preparation | ✅ Complete |

---

## 📅 Immediate Next Actions (Now → 2026-05-10)

### Week 1 (2026-04-13 – 2026-04-18): Beta Launch

- [ ] **Publish the beta announcement** on GitHub Discussions using the template in [BETA-TESTING-PROGRAM.md](BETA-TESTING-PROGRAM.md)
- [ ] **Share on social media** — tweet/post about beta opening with link to GitHub repo
- [ ] **Set up staging environment** for beta testers (recommend Cloudways or WP Engine staging)
- [ ] **Create GitHub Discussion categories:** Beta Applications, Beta Feedback, Q&A
- [ ] **Tag `v6.0.0-beta` release** on GitHub with `Release → Create new release`
- [ ] **Build the plugin zip** for beta testers: `cd mu-plugins/pearblog-engine && composer install --no-dev && zip -r pearblog-engine-v6.0.0-beta.zip .`

### Week 2 (2026-04-19 – 2026-04-25): Beta Week 1 Support

- [ ] Triage all beta feedback from GitHub Discussions
- [ ] Fix any `beta: blocker` issues immediately
- [ ] Weekly summary post in GitHub Discussions
- [ ] Run the full test suite on staging to confirm CI passes
- [ ] Monitor error_log and AlertManager on the staging site

### Week 3 (2026-04-26 – 2026-05-02): Beta Week 2 + Final Polish

- [ ] Apply fixes from Week 2 triage
- [ ] Cut `v6.0.0-rc1` (Release Candidate) on GitHub
- [ ] Update [CHANGELOG.md](CHANGELOG.md) with v6.0.0 full release notes
- [x] Add `examples/postman/PearBlog-Engine-v6.postman_collection.json` to the repo ✅
- [ ] Verify all links in [DOCUMENTATION-INDEX.md](DOCUMENTATION-INDEX.md) are live

### Week 4 (2026-05-03 – 2026-05-09): Pre-Launch Sign-off

- [ ] Final run-through of [PRE-LAUNCH-CHECKLIST.md](PRE-LAUNCH-CHECKLIST.md)
- [ ] Confirm monitoring stack is live (AlertManager Slack/Discord webhooks configured)
- [ ] Write the public launch blog post / press release
- [ ] Prepare the [LAUNCH-DAY-PLAN.md](LAUNCH-DAY-PLAN.md) hour-by-hour runbook
- [ ] Brief any support team members

### Launch Day (2026-05-10): 🚀 v7.0.0

- [ ] Merge to `main` and tag `v7.0.0`
- [ ] Publish GitHub Release with full changelog
- [ ] Send launch announcement email
- [ ] Post on all channels (Twitter/X, LinkedIn, Reddit r/Wordpress)
- [ ] Submit to WordPress.org plugin directory (if applicable)

---

## 🗺️ v7.1 Feature Roadmap (2026-06-01)

These are the next-generation features planned for the first post-launch release:

### GraphQL API ✅ Done — v7.4.0
- ~~Expose all REST endpoints as GraphQL queries/mutations~~ ✅ `GraphQLController.php` — WPGraphQL types + standalone `/pearblog/v1/graphql` endpoint
- ~~Enable headless WordPress use cases~~ ✅ Supports `queue`, `stats`, `topPosts`, `health` queries
- ~~Integrate with WPGraphQL plugin if available~~ ✅ `graphql_register_types` hook auto-registers types if WPGraphQL is active

### Advanced Analytics Dashboard ✅ Done — v7.4.0
- ~~Per-post traffic integration (Google Analytics 4 API)~~ ✅ `GA4Client.php` — service-account JWT auth + Data API v1beta
- ~~Revenue attribution per AI-generated article~~ ✅ `performance_score` blends views × quality score
- ~~Content performance ranking (views × quality score)~~ ✅ `AnalyticsDashboard::get_top_performing_posts()`
- ~~Admin tab: "Analytics" with interactive charts~~ ✅ `AnalyticsDashboard::get_summary()` + per-post meta sync

### A/B Testing Framework ✅ Done — v7.1.0
- ~~Split-test two prompt templates for the same topic~~ ✅ `modifier_a` / `modifier_b` appended to the `pearblog_prompt` filter
- ~~Track which variant achieves higher quality score~~ ✅ Scores recorded via `pearblog_pipeline_completed` action
- ~~Auto-promote winning variant after 7 days~~ ✅ Daily cron + `promote_mature_tests()`
- ~~New class: `src/Testing/ABTestEngine.php`~~ ✅

### Monitoring Dashboard UI (Real-Time) ✅ Done — v6.0.2
- ~~Add "Monitoring" tab to the admin panel~~ ✅ Tab already existed; now fully populated
- ~~Display PerformanceDashboard metrics in live Chart.js graphs~~ → Static tables (Chart.js deferred to v7.2 post-launch)
- ~~Alert history view with filter by level/date~~ ✅ Level filter + history table added
- ~~Cost tracking visualization (daily/weekly/monthly)~~ ✅ Daily table + summary card already render cost data
- ~~Export metrics to CSV/JSON~~ ✅ CSV download button + existing JSON REST endpoint

---

## 🗺️ v7.2 AI Enhancements (2026-07-01)

### GPT-4o Support ✅ Done — v7.2.0
- ~~Update `AIClient.php` model options to include `gpt-4o`~~ ✅ `MODELS` constant with gpt-4o, gpt-4o-mini, gpt-4-turbo, gpt-3.5-turbo
- ~~Add a model selector dropdown in Admin → General → AI Settings~~ ✅ `pearblog_ai_model` option + select row in General tab
- ~~Update cost-per-token calculations for new models~~ ✅ Per-model input/output cost rates + `estimate_cost_cents()`

### Multi-Model Support ✅ Done — v7.2.1
- ~~Support Anthropic Claude 3 via a swappable `AIProviderInterface`~~ ✅ `AnthropicProvider` (Claude 3.5 Sonnet + Claude 3 Haiku)
- ~~Support Google Gemini Pro via the same interface~~ ✅ `GeminiProvider` (Gemini 1.5 Pro + Flash)
- ~~Factory pattern: `AIProviderFactory::make('anthropic'|'openai'|'gemini')`~~ ✅ `AIProviderFactory` with full metadata API

### Advanced Prompt Engineering ✅ Done — v7.3.0
- ~~Dynamic few-shot examples pulled from top-performing past articles~~ ✅ `FewShotEngine.php` — configurable score threshold + excerpt length
- ~~Persona builder: configure author voice/style~~ ✅ `PersonaBuilder.php` — named personas with name/bio/style/tone/vocabulary
### Competitive gap analysis ✅ Done — v7.4.0
- ~~Scrape SERPs and inject missing topics into prompt~~ ✅ `CompetitiveGapEngine.php` — Jaccard similarity gap analysis + prompt injection

---

## 🗺️ v7.3 Enterprise Features (2026-08-01)

### White-Label Options ✅ Done — v7.3.0
- ~~Custom plugin slug, admin menu name, and branding~~ ✅ `WhiteLabelManager.php` — brand name, menu label, logo URL, accent colour
- ~~Remove all "PearBlog" references from front-end output via settings~~ ✅ All labels override-able via WP options
- ~~Add a "Branding" tab to the admin page~~ ✅ Settings registered under `pearblog_branding` settings group

### Advanced Permissions ✅ Done — v7.3.0
- ~~Role-based access control for pipeline trigger/pause~~ ✅ `PermissionManager.php` — configurable per-action role lists
- ~~Per-site API key scoping in multi-site networks~~ ✅ Stored per blog_id via existing `get_blog_option()` pattern
- ~~Audit log of all WP-CLI and admin actions~~ ✅ Ring-buffer audit log (500 entries) with actor, action, timestamp

### SLA Management ✅ Done — v7.3.0
- ~~Configurable per-site SLA targets (uptime, response time)~~ ✅ `SLAManager.php` — uptime %, pipeline success %, API response ms, cost per article
- ~~Auto-page on SLA breach via PagerDuty (already integrated in AlertManager)~~ ✅ `pearblog_sla_breached` action fired on breach (AlertManager hooks in)
- ~~Monthly SLA report generated and emailed automatically~~ ✅ Monthly cron + `generate_monthly_report()` + e-mail dispatch

---

## 🗺️ v7.5 Content Automation 2.0 (2026-09-01)

### SERP Scraper ✅ Done — v7.5.0
- ~~Real-time competitor article fetch~~ ✅ `SerpScraper.php` — Value SERP + Serper.dev providers; results cached; `fetch_titles()` feeds `CompetitiveGapEngine`

### Auto-Keyword Clustering ✅ Done — v7.5.0
- ~~Auto-keyword clustering from GA4 search terms~~ ✅ `KeywordClusterEngine.php` — IDF + Jaccard similarity; weekly cron refresh; `KeywordCluster` value objects

### Multilingual Content Generation ✅ Done — v7.5.0
- ~~Multilingual content generation (WPML/Polylang integration)~~ ✅ `MultilingualManager.php` — AI translation, WPML + Polylang native hook integration, `pearblog_translation_created` action

---

## 🗺️ v7.6 Performance & Infrastructure ✅ Done — 2026-04-13

### Object Cache Integration ✅ Done — v7.6.0
- ~~Object cache integration (Redis/Memcached via WP_Object_Cache)~~ ✅ `ObjectCacheAdapter.php` — wraps `wp_cache_*` API; transparent Redis/Memcached/APCu support; multisite global group; typed helpers for AI content/SEO/links/duplicates

### Async Pipeline ✅ Done — v7.6.0
- ~~Async pipeline via WP Background Processing library~~ ✅ `BackgroundProcessor.php` — persistent WP-Cron job queue; `dispatch()` + `handle_batch()`; exponential back-off retry; action-decoupled via `pearblog_bg_run_pipeline`

### CDN Image Auto-Offload ✅ Done — v7.6.0
- ~~CDN image auto-offload (Cloudflare/BunnyCDN integration)~~ ✅ `CdnManager.php` — BunnyCDN Storage + Cloudflare Images; transparent URL rewriting; `_pearblog_cdn_url` meta; `pearblog_cdn_offloaded` action

---

## 🗺️ v7.7 Developer Experience & Extensibility (Planned 2026-11-01)

- Plugin hooks reference documentation
- Developer CLI scaffolding tools
- Event-sourced pipeline audit log API



These are known issues to resolve in the first patch releases:

| Issue | File | Priority |
|-------|------|----------|
| `pearblog_booking_api_key` not registered with `register_setting()` | Fixed ✅ (registered at AdminPage.php:139) | Done |
| AutopilotRunner test used `assertMatchesRegularExpression` (PHPUnit 8.5 compat) | Fixed ✅ | Done |
| Postman collection JSON referenced in API docs but not committed | Fixed ✅ `examples/postman/PearBlog-Engine-v6.postman_collection.json` | Done |
| `ESCALATION_LEVELS` constant defined but not used in AlertManager | Fixed ✅ `src/Monitoring/AlertManager.php` — enforces min severity per priority | Done |
| No rate-limit headers actually set on REST responses (only documented) | Fixed ✅ `src/API/RateLimiter.php` + `AutomationController.php` — 429 + X-RateLimit-* headers | Done |

---

## 📊 Success Metrics to Track Post-Launch

| Metric | v6.0 Baseline | v7.0 Target | v7.5 Target |
|--------|---------------|-------------|-------------|
| Unit tests passing | 218 / 440 assertions | 250+ | 300+ |
| Active beta testers | 0 | 5–10 | — |
| Active installations | 0 | 5+ | 50+ |
| Articles generated | 0 | 10,000+ | 100,000+ |
| Pipeline success rate | 99.2% | 99.5%+ | 99.9%+ |
| Cost per article | $0.08 | ≤$0.08 | ≤$0.05 |
| GitHub stars | — | 25+ | 200+ |

---

## 🔗 Key Files to Reference

| File | Purpose |
|------|---------|
| [LAUNCH-DAY-PLAN.md](LAUNCH-DAY-PLAN.md) | Hour-by-hour launch runbook |
| [BETA-TESTING-PROGRAM.md](BETA-TESTING-PROGRAM.md) | Beta recruitment + test plan |
| [PRE-LAUNCH-CHECKLIST.md](PRE-LAUNCH-CHECKLIST.md) | Sign-off checklist |
| [CHANGELOG.md](CHANGELOG.md) | Version history |
| [API-DOCUMENTATION.md](API-DOCUMENTATION.md) | REST API reference |
| [ROADMAP-VISUAL.md](ROADMAP-VISUAL.md) | Visual roadmap v6→v7 |
| [DOCUMENTATION-INDEX.md](DOCUMENTATION-INDEX.md) | All 25+ docs indexed |

---

*Last updated: 2026-04-13 — v7.6.0 complete (ObjectCacheAdapter, BackgroundProcessor, CdnManager); 588 tests passing*
