# Video Tutorials — PearBlog Engine

> **Version:** 6.0  
> This document lists planned video tutorials, their scripts/outlines, and instructions for recording.  
> Videos are hosted on YouTube at: **https://www.youtube.com/@PearBlogEngine** *(to be created)*

---

## Tutorial Overview

| # | Title | Duration | Audience | Status |
|---|-------|----------|----------|--------|
| 1 | Quick Start (5 min) | 5 min | New users | 📋 Planned |
| 2 | Full Setup Walkthrough | 15–20 min | Developers | 📋 Planned |
| 3 | Admin Panel Tour | 10 min | Site owners | 📋 Planned |
| 4 | Troubleshooting Common Issues | 10 min | All users | 📋 Planned |
| 5 | Advanced Configuration | 15 min | Power users | 📋 Planned |

---

## Tutorial 1: Quick Start (5 min)

**Target audience:** Blog owners who want to get PearBlog generating content in < 5 minutes.

### Script Outline

```
[0:00–0:30] INTRO
- Show the final result: auto-published blog post with AI image
- "In 5 minutes you'll have PearBlog generating content automatically"

[0:30–1:30] INSTALLATION
- Upload plugin ZIP via Plugins → Add New
- Activate plugin
- Show "PearBlog Engine" menu appears in sidebar

[1:30–3:00] BASIC CONFIGURATION
- Navigate to PearBlog Engine → Settings → General
- Enter OpenAI API key (show where to get it at platform.openai.com)
- Set industry/niche (e.g., "fitness", "travel", "finance")
- Set tone ("professional", "conversational")
- Save settings

[3:00–4:00] GENERATE FIRST POST
- Run: wp pearblog queue --topics="10 benefits of yoga"
- Run: wp pearblog generate
- Show draft post appearing in Posts

[4:00–5:00] OUTRO
- Show published post with image
- Point to full setup tutorial for advanced features
- Subscribe CTA
```

---

## Tutorial 2: Full Setup Walkthrough (15–20 min)

**Target audience:** Developers and technically-minded site owners.

### Script Outline

```
[0:00–1:00] INTRO
- Overview of PearBlog Engine architecture
- What we'll cover in this video

[1:00–3:00] SERVER REQUIREMENTS & INSTALLATION
- PHP 8.0+, MySQL 5.7+, WordPress 6.0+
- Composer dependencies
- mu-plugin vs regular plugin installation

[3:00–6:00] API CONFIGURATION
- OpenAI API key: where to get it, pricing overview
- Set token budget via options
- Test AI connection: wp pearblog generate --dry-run

[6:00–9:00] CONTENT PIPELINE CONFIGURATION
- Industry and tone settings
- Publish rate (posts/day)
- Language selection
- Topic queue: manual add vs automation

[9:00–12:00] MONITORING SETUP
- AlertManager: Slack webhook, Discord webhook, email
- HealthController: /wp-json/pearblog/v1/health endpoint
- PerformanceDashboard: Admin → PearBlog Engine → Monitoring tab

[12:00–15:00] AUTOMATION
- Autonomous mode: wp pearblog autopilot start
- CronManager: review scheduled events
- Content calendar: scheduling posts ahead

[15:00–18:00] THEME SETUP (optional)
- Activate PearBlog theme
- Reading progress bar, dark mode
- Email capture widget

[18:00–20:00] PRODUCTION HARDENING
- Add PEARBLOG_ENGINE_API_KEY to wp-config.php
- Review DEPLOYMENT.md checklist
- Enable object cache (Redis)
```

---

## Tutorial 3: Admin Panel Tour (10 min)

**Target audience:** Non-technical site owners managing the plugin.

### Script Outline

```
[0:00–0:30] INTRO
- Overview of admin panel location

[0:30–2:00] GENERAL TAB
- API key status indicator (green = valid)
- Industry, tone, language
- Publish rate slider

[2:00–3:30] AI IMAGES TAB
- Enable/disable DALL-E
- Image style presets
- Alt text AI generation

[3:30–5:00] MONETIZATION TAB
- AdSense ID
- Booking.com / affiliate link injection
- SaaS products section

[5:00–6:30] EMAIL TAB
- ESP selection (Mailchimp, ConvertKit, wp_mail)
- API keys for each provider
- Email digest schedule

[6:30–8:00] AUTOMATION TAB
- Autonomous mode toggle
- Topic generation settings
- WP-CLI quick reference

[8:00–9:30] QUEUE TAB
- View current topic queue
- Add topics manually
- Pause/resume queue

[9:30–10:00] DASHBOARD WIDGET DEMO
- Show the admin dashboard widget
- Queue size, posts today, AI cost
```

---

## Tutorial 4: Troubleshooting Common Issues (10 min)

**Target audience:** All users encountering errors.

### Script Outline

```
[0:00–0:30] INTRO
- "If something goes wrong, here's how to debug it"

[0:30–2:30] CHECKING HEALTH STATUS
- Visit /wp-json/pearblog/v1/health in browser
- Explain each field: openai_connected, queue_size, last_run
- Show healthy vs unhealthy response

[2:30–4:00] AI FAILURES & CIRCUIT BREAKER
- wp pearblog stats — show failure count
- wp pearblog circuit reset — reset circuit breaker
- Check pearblog_ai_cost_cents option for budget overage

[4:00–5:30] PIPELINE ERRORS
- Enable WP_DEBUG and WP_DEBUG_LOG
- Check /wp-content/debug.log for "pearblog" entries
- Common errors: invalid API key, rate limit, timeout

[5:30–7:00] IMAGE ISSUES
- DALL-E disabled or quota exceeded
- Check PHP memory_limit (512 MB recommended)
- Verify GD or Imagick is available

[7:00–8:30] DATABASE ISSUES
- Duplicate content warnings
- Run: wp pearblog duplicate --post_id=ID
- Run: wp pearblog links --post_id=ID

[8:30–10:00] GETTING HELP
- TROUBLESHOOTING.md guide
- GitHub Issues: https://github.com/AndyPearman89/PearBlog-Engine-/issues
- Community Discord (link TBD)
```

---

## Tutorial 5: Advanced Configuration (15 min)

**Target audience:** Power users and agencies managing multiple sites.

### Script Outline

```
[0:00–0:30] INTRO
- What "advanced" means in PearBlog context

[0:30–3:00] MULTI-SITE / MULTI-TENANT
- SiteProfile and TenantContext
- Per-site configuration via blog options
- wp pearblog autopilot start (per-site context)

[3:00–5:30] CUSTOM PROMPT BUILDERS
- Factory: how niche auto-detection works
- Creating a custom builder (extends PromptBuilder)
- Register via pearblog_prompt_builder_factory filter

[5:30–7:30] CONTENT CACHING
- ContentCache internals
- When to manually flush: wp pearblog cache flush
- Monitoring cache hit rate in PerformanceDashboard

[7:30–9:30] WEBHOOK INTEGRATIONS
- Register outbound webhooks via REST API
- Payload structure for each event type
- Signature verification (HMAC-SHA256)

[9:30–11:30] CONTENT CALENDAR
- Scheduling topics for specific dates
- REST API for programmatic scheduling
- Integration with editorial workflows

[11:30–13:30] MONITORING & ALERTING
- AlertManager: configure thresholds
- Logger: structured JSON logs
- PerformanceDashboard: reading the charts

[13:30–15:00] OUTRO
- Link to API documentation
- Contributing guide
- Patreon / sponsorship CTA
```

---

## Recording Instructions

### Equipment
- Screen recording: **OBS Studio** (free) or **Loom**
- Microphone: Any USB mic; Blue Yeti or Rode NT-USB recommended
- Resolution: **1920×1080** minimum

### Setup Checklist
- [ ] Clean browser bookmarks bar
- [ ] Dark mode or light mode theme — consistent throughout series
- [ ] WordPress admin with sample content loaded
- [ ] OpenAI test key with small budget ($5) for live demos
- [ ] Practice run-through before recording

### Post-Production
- Edit with **DaVinci Resolve** (free) or **iMovie**
- Add chapter markers at each section (YouTube chapters)
- Captions: auto-generated via YouTube, then manually corrected
- Polish translation: provide translated script to community volunteer

### Upload Checklist
- [ ] Title includes "PearBlog Engine" for SEO
- [ ] Thumbnail: branded PearBlog template (see brand-assets/)
- [ ] Description includes version number and links to docs
- [ ] Add to "PearBlog Engine Tutorials" playlist
- [ ] Pin best comment with timestamp index
- [ ] Update this file with YouTube URL after upload

---

## Links

| Resource | URL |
|----------|-----|
| YouTube Channel | https://www.youtube.com/@PearBlogEngine *(TBC)* |
| Documentation | See DOCUMENTATION-INDEX.md |
| TROUBLESHOOTING.md | ./TROUBLESHOOTING.md |
| API Documentation | ./API-DOCUMENTATION.md |
