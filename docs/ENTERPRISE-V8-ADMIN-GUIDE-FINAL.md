# Enterprise v8 Admin Guide — FINAL VERIFIED

**PearBlog Engine v8.0.0 — Enterprise Admin Panel**

**Status:** Production-Ready (v8-enterprise mode)  
**Last Updated:** 2026-06-21  
**Verified Against:** Commit c96a721b8818f9e44061923d40018a9b9678b651

---

## Quick Start

### Access the Admin Panel

Navigate to your WordPress admin and go to:
```
/wp-admin/admin.php?page=pearblog-enterprise-v8
```

Or click **🚀 PearBlog v8** in the WordPress admin menu (top position).

### Enable Enterprise v8 Mode

In `pearblog-engine.php` (already enabled by default in v8.0.0+):
```php
define( 'PEARBLOG_ADMIN_VERSION', 'v8-enterprise' );
```

**Alternative modes:**
- v7: `define( 'PEARBLOG_ADMIN_VERSION', 'v7' );`
- v6: `define( 'PEARBLOG_ADMIN_VERSION', 'v6' );`

---

## System Requirements

- **WordPress:** 5.8+
- **PHP:** 8.0+
- **User Role:** Administrator (`manage_options` capability)
- **Browser:** Chrome 90+, Firefox 88+, Safari 14+, Edge 90+

---

## Interface Overview

### Top Bar (Header)

The header provides quick access to:
- **🍐 PearBlog Enterprise** — Logo and version badge (v8.0.0 MAX)
- **Theme Toggle** (🌙/☀️) — Switch between light and dark modes
- **Notifications** (🔔) — Real-time notification center with unread badge
- **User Profile** — Current user avatar and account info

### 15-Tab Navigation

The admin panel features 15 specialized tabs organized by function:

| Group | Tabs |
|-------|------|
| **Core Operations** | 🎯 Dashboard Enterprise, 📊 Real-Time Analytics |
| **Content & Strategy** | 🧠 AI Strategy, ✍️ Content Engine, 🔍 SEO Advanced |
| **Business** | 💰 Revenue Center, 👥 Leads & CRM |
| **Automation & Analytics** | ⚙️ Automation Pro, 📈 Analytics Deep |
| **Infrastructure** | 🌐 Multisite/SaaS, ⚡ Performance |
| **Security & Admin** | 🔒 Security & Audit, 📋 Advanced Reports, 🔗 Integrations, ⚙️ Settings Enterprise |

---

## Tab Reference

### 1. 🎯 Dashboard Enterprise

**Purpose:** Executive-level overview with KPIs

**Real Data Tracked:**
- Revenue (today + 30-day trend)
- Active user count
- Content generated (today)
- AI API costs
- 30-day revenue trend chart (Chart.js)
- Content distribution pie chart
- Recent activity feed (last 10 events)

**Data Source:** `wp_posts`, `wp_pb_revenue` (if enabled), WordPress options

---

### 2. 📊 Real-Time Analytics

**Purpose:** Live monitoring with auto-refresh

**Features:**
- **Live Visitors** — Current site visitors
- **Revenue/Hour** — Hourly revenue rate
- **Conversions** — Real-time conversion count
- **Error Rate** — System error percentage
- **Live Activity Stream** — Real-time event log

**Technical:**
- AJAX polling (5-second intervals)
- Action: `pb_v8_get_realtime_stats`
- Can be disabled via `pearblog_v8_realtime_enabled` option
- Data is mocked in current version (placeholder implementation)

---

### 3. 🧠 AI Strategy

**Purpose:** AI-driven keyword discovery and content planning

**Features:**
- **Keyword Source Management:**
  - Manual — Add keywords manually
  - Automatic — AI discovers keywords
  - Competitive Scraping — Extract trending topics
  - Hybrid — Combine all sources
- **Intent Prioritization:**
  - Balanced (default)
  - TOFU-focused (top of funnel)
  - MOFU-focused (middle of funnel)
  - BOFU-focused (bottom of funnel)

**Configuration Options:**
```bash
wp option get pearblog_keyword_source
wp option get pearblog_intent_priority
wp option get pearblog_auto_keyword_discovery
wp option get pearblog_discovery_daily_limit
wp option get pearblog_keyword_scraping_enabled
```

---

### 4. ✍️ Content Engine

**Purpose:** Batch content generation and management

**Features:**
- **Batch Generation:** Generate 1-100 articles at once
- **Publish Control:** Publish immediately or save as drafts
- **Image Generation:** Optional DALL-E 3 featured images
- **Queue Status:** Shows pending topics in queue

**Workflow:**
1. Add topics to queue (via Automation tab)
2. Set batch count (1-100)
3. Choose publish/draft option
4. Enable image generation (if configured)
5. Click "Start Batch Generation"
6. Monitor progress in Activity Feed

---

### 5. 🔍 SEO Advanced

**Purpose:** SEO optimization and meta management

**Features:**
- Meta title/description optimization
- Schema.org structured data (JSON-LD)
- XML sitemap management
- Keyword tracking
- Internal linking suggestions
- SEO audit reports

**Configuration Options:**
```bash
wp option get pearblog_seo_automation_enabled
wp option get pearblog_internal_links_enabled
wp option get pearblog_internal_links_count
wp option get pearblog_meta_optimization_enabled
wp option get pearblog_schema_enabled
wp option get pearblog_sitemap_enabled
wp option get pearblog_programmatic_seo_enabled
```

---

### 6. 💰 Revenue Center

**Purpose:** Monetization and revenue tracking

**Features:**
- **AdSense Configuration:**
  - Publisher ID management
  - Enable/disable AdSense
  - Strategy selection (aggressive/balanced/conservative)
  - Per-stage control (TOFU/MOFU/BOFU)
- **Revenue Analytics:**
  - Daily/weekly/monthly charts
  - RPM (revenue per thousand)
  - Ad placement performance
- **Funnel-Aware Monetization:**
  - Light ads for TOFU (awareness)
  - Moderate ads for MOFU (consideration)
  - Aggressive ads for BOFU (decision)

**Configuration:**
```bash
wp option update pearblog_adsense_enabled 1
wp option update pearblog_adsense_publisher_id "ca-pub-XXXXXXXXX"
wp option update pearblog_adsense_strategy "funnel_aware"
wp option update pearblog_v7_revenue_enabled 1
```

---

### 7. 👥 Leads & CRM

**Purpose:** Lead capture and expert management

**Features:**
- Lead capture form configuration
- Lead placement options (before/after content, sidebar, popup)
- Expert profile management
- Lead scoring and qualification
- Conversion funnel analysis
- Export to CSV

**Database Table:**
```sql
-- Leads are stored here (if enabled)
SELECT * FROM wp_pearblog_leads;
```

**Configuration:**
```bash
wp option update pearblog_leads_enabled 1
wp option update pearblog_experts_enabled 1
wp option update pearblog_leads_placement "after_content"
```

---

### 8. ⚙️ Automation Pro

**Purpose:** Workflow automation and scheduling

**Features:**
- Queue status overview
- Publishing schedule configuration
- Auto-publishing toggle
- Publishing rate (1-24 articles/hour)
- Cron job management
- Automation workflow triggers

**Available Options:**
```bash
wp option get pearblog_publish_rate
wp option get pearblog_auto_publish
wp option get pearblog_schedule_enabled
wp option get pearblog_cron_enabled
```

---

### 9. 📈 Analytics Deep

**Purpose:** Advanced analytics with custom filtering

**Features:**
- Date range filtering (custom ranges)
- Category filtering
- Performance metrics by category
- Engagement analysis
- Traffic trends
- Custom report generation

**Filter Parameters:**
- From date / To date
- Category selection
- Post type filtering

---

### 10. 🌐 Multisite/SaaS

**Purpose:** Multi-tenant management for WordPress Multisite

**Features:**
- Site provisioning (create/delete subsites)
- Network-wide settings
- Tenant context switching
- Per-site configuration
- Bulk operations across sites

**Requirements:**
- `WP_ALLOW_MULTISITE` must be enabled in wp-config.php
- Multisite must be set up in WordPress

**Configuration:**
```bash
wp option update pearblog_centralized_api_keys 1
wp option update pearblog_multisite_bulk_actions 1
```

---

### 11. ⚡ Performance

**Purpose:** Performance monitoring and system health

**Features:**
- Database query analysis
- Slow query detection
- PHP memory usage tracking
- Response time monitoring
- Cache metrics (Redis/Memcached if enabled)
- Health checks endpoint: `/wp-json/pearblog/v1/health`

**Health Endpoint Response:**
```json
{
  "overall": "ok|degraded|down",
  "timestamp": "2026-06-21 12:34:56",
  "api_key": { "status": "ok", "detail": "configured" },
  "circuit_breaker": { "status": "ok", "detail": "closed" },
  "openai": { "status": "ok", "detail": "reachable" },
  "queue": { "status": "ok", "detail": "2 topics waiting", "count": 2 },
  "last_run": { "status": "ok", "detail": "2026-06-21 11:30:00", "hours_since": 1.1 },
  "ai_cost": { "status": "ok", "usd_cents": 2450, "usd": 24.50 },
  "articles_today": { "status": "ok", "count": 5 }
}
```

---

### 12. 🔒 Security & Audit

**Purpose:** Security scoring and audit logging

**Features:**
- **Security Score:** 0-100 rating (dynamically calculated)
- **Failed Login Tracking:** Monitor suspicious attempts
- **Blocked IP Addresses:** Auto-block after failed attempts
- **Audit Log:** All admin actions with timestamp, user, IP
- **Export:** Download audit logs as CSV

**Audit Log Columns:**
- Timestamp (YYYY-MM-DD HH:MM:SS)
- User (username)
- Action (e.g., "Settings Updated")
- IP Address
- Status (success/failed)

**Note:** Audit logging is currently a UI skeleton. Data tracking requires custom implementation.

---

### 13. 📋 Advanced Reports

**Purpose:** Comprehensive reporting with export

**Features:**
- **Revenue Report** — Detailed revenue analysis
- **Content Performance** — Top articles, engagement, traffic
- **SEO Report** — Rankings, impressions, CTR
- **AI Cost Analysis** — API usage breakdown

**Export Formats:**
- CSV (UTF-8 with BOM for Excel compatibility)
- JSON (for API consumption)

**Note:** Export functionality is currently a placeholder (`// Mock export`). Full implementation required for production.

---

### 14. 🔗 Integrations

**Purpose:** Third-party API management

**Features:**
- **Google Analytics** — GA4 property connection
- **Google AdSense** — Publisher account linkage
- **Google Search Console** — Site verification
- **Mailchimp** — Email list sync
- **API Status** — Monitor all connected services

**Connection Status:**
- ✅ Active (green)
- ⚠️ Pending (yellow)
- ❌ Failed (red)

**Note:** Integration scaffolding exists. Specific API implementations require custom plugin additions.

---

### 15. ⚙️ Settings Enterprise

**Purpose:** System-wide configuration

**Features:**
- Global plugin settings
- AI provider configuration (OpenAI, Anthropic, Cohere)
- Database settings
- Email notifications
- Debug mode toggle
- Feature flags

**Key Options:**
```bash
wp option get pearblog_openai_api_key
wp option get pearblog_ai_model
wp option get pearblog_enable_image_generation
wp option get pearblog_homepage_version
wp option get pearblog_admin_version
```

---

## Core Features

### Dark Mode Support

**Persistent Theme Preference:**
- Toggle via button in top bar (🌙/☀️)
- Automatically saved to `pearblog_v8_theme` option
- Persists across sessions
- CSS variables switch automatically

**Toggle Function:**
```javascript
// Triggered by theme toggle button
action: 'pb_v8_toggle_theme'
```

**Option Storage:**
```bash
wp option get pearblog_v8_theme  # Returns: "light" or "dark"
```

---

### Real-Time Updates

**AJAX Polling (5-second intervals):**
- Disabled on non-dashboard tabs by default
- Only active on Dashboard and Real-Time Analytics tabs
- Can be globally disabled via option

**Endpoint:**
- Action: `pb_v8_get_realtime_stats`
- Returns: `{ visitors, revenue, conversions, errors }`

**Configuration:**
```bash
wp option update pearblog_v8_realtime_enabled 0  # Disable
wp option update pearblog_v8_realtime_enabled 1  # Enable (default)
```

**Note:** Current implementation is a placeholder. Real data requires custom metrics collection.

---

### Language Support

**English & Polish Toggle:**
```bash
wp option get pearblog_v8_language  # Returns: "en" or "pl"
```

**Translation System:**
- Uses WordPress i18n functions (`__()`, `_e()`)
- Language toggle in top bar (🇬🇧/🇵🇱)
- Full text domain: `pearblog-engine`

---

### Design System

**CSS Variables (Glassmorphism + Modern Gradients):**

Light Mode:
```css
--pb-v8-primary: #0066ff;
--pb-v8-bg-primary: #ffffff;
--pb-v8-text-primary: #1a1a1a;
--pb-v8-glass-bg: rgba(255, 255, 255, 0.85);
```

Dark Mode:
```css
--pb-v8-bg-primary: #0a0e1a;
--pb-v8-text-primary: #ffffff;
--pb-v8-glass-bg: rgba(20, 24, 37, 0.85);
```

**Components:**
- Glassmorphism cards (backdrop-filter blur effect)
- Smooth transitions (150ms–350ms)
- Interactive animations (pulse, fade, slide)
- Responsive grid system

---

## WordPress Hooks

### Registered AJAX Handlers

| Action | Handler | Response |
|--------|---------|----------|
| `wp_ajax_pb_v8_get_realtime_stats` | `ajax_get_realtime_stats()` | Real-time metrics |
| `wp_ajax_pb_v8_get_notifications` | `ajax_get_notifications()` | Notification list |
| `wp_ajax_pb_v8_toggle_theme` | `ajax_toggle_theme()` | Theme preference |
| `wp_ajax_pb_v8_export_report` | `ajax_export_report()` | Report export |

### WordPress Options

```bash
# Theme & Display
wp option get pearblog_v8_theme          # "light" or "dark"
wp option get pearblog_v8_language       # "en" or "pl"
wp option get pearblog_v8_realtime_enabled

# Feature Toggles
wp option get pearblog_seo_automation_enabled
wp option get pearblog_adsense_enabled
wp option get pearblog_leads_enabled

# Configuration
wp option get pearblog_openai_api_key
wp option get pearblog_publish_rate
wp option get pearblog_adsense_publisher_id
```

---

## Frontend Assets

### CSS

**File:** `mu-plugins/pearblog-engine/assets/css/admin-v8-enterprise.css`
- **Size:** ~25 KB (minimized)
- **Features:**
  - CSS custom properties (variables)
  - Glassmorphism design
  - Dark mode support
  - Responsive layouts
  - Accessibility (focus states, reduced-motion)

### JavaScript

**File:** `mu-plugins/pearblog-engine/assets/js/admin-v8-enterprise.js`
- **Dependencies:** jQuery, Chart.js, Alpine.js
- **Size:** ~12 KB (minimized)
- **Features:**
  - Tab switching
  - Real-time polling
  - Theme toggling
  - Notifications
  - Chart rendering

**Global Object:** `pbV8Data`
```javascript
{
  ajaxUrl: '/wp-admin/admin-ajax.php',
  nonce: 'wp_create_nonce("pb_v8_nonce")',
  currentTab: 'dashboard',
  theme: 'light',
  language: 'en',
  realtimeEnabled: true,
  version: '8.0.0',
  translations: { /* i18n strings */ }
}
```

---

## REST API Health Endpoint

### Endpoint: `/pearblog/v1/health`

**Method:** GET  
**Access:** Requires `manage_options` or valid health secret

**Request:**
```bash
curl -i https://yoursite.com/wp-json/pearblog/v1/health
```

**Response (HTTP 200):**
```json
{
  "overall": "ok",
  "timestamp": "2026-06-21 12:34:56",
  "api_key": {
    "status": "ok",
    "detail": "configured"
  },
  "circuit_breaker": {
    "status": "ok",
    "detail": "closed"
  },
  "openai": {
    "status": "ok",
    "detail": "reachable"
  },
  "queue": {
    "status": "ok",
    "detail": "2 topics waiting",
    "count": 2
  },
  "last_run": {
    "status": "ok",
    "detail": "2026-06-21 11:30:00",
    "hours_since": 1.1
  },
  "ai_cost": {
    "status": "ok",
    "usd_cents": 2450,
    "usd": 24.50
  },
  "articles_today": {
    "status": "ok",
    "count": 5
  }
}
```

**Response (HTTP 503 - System Down):**
```json
{
  "overall": "down",
  "timestamp": "2026-06-21 12:34:56",
  "api_key": {
    "status": "error",
    "detail": "not configured"
  },
  ...
}
```

**Authentication:**
```bash
# Option 1: Via header (recommended for monitoring)
curl -H "X-PearBlog-Health-Secret: your-secret" \
  https://yoursite.com/wp-json/pearblog/v1/health

# Option 2: Via query parameter
curl https://yoursite.com/wp-json/pearblog/v1/health?health_secret=your-secret

# Option 3: Via WordPress admin capability
# Must be logged in as admin
```

---

## Troubleshooting

### Admin Page Not Loading

**Check 1: Verify constant is set**
```bash
grep "PEARBLOG_ADMIN_VERSION" wp-content/mu-plugins/pearblog-engine/pearblog-engine.php
# Should show: define( 'PEARBLOG_ADMIN_VERSION', 'v8-enterprise' );
```

**Check 2: Clear cache**
```bash
wp cache flush
wp rewrite flush
```

**Check 3: Check browser console for errors**
- Open DevTools (F12)
- Go to Console tab
- Look for any JavaScript errors

---

### Health Endpoint Returns 404

**Step 1: Verify route is registered**
```bash
wp rest route list | grep pearblog/v1/health
```

**Step 2: Check MU-plugin files are synced**
```bash
ls -la wp-content/mu-plugins/pearblog-engine/src/Monitoring/HealthController.php
ls -la wp-content/mu-plugins/pearblog-engine/src/Core/Plugin.php
```

**Step 3: Flush rewrite rules**
```bash
wp rewrite flush --hard
```

**Step 4: Re-test**
```bash
curl -i https://yoursite.com/wp-json/pearblog/v1/health
```

If still 404, the route definition may not be loaded. See **Emergency Health Fix** below.

---

### Emergency Health Fix

If `/pearblog/v1/health` is still returning `rest_no_route` after sync:

**Option 1: Use standalone health fixer MU-plugin**
```bash
# File exists in repo:
wp-content/mu-plugins/pearblog-health-fix.php

# This independently registers the health endpoint
# and bypasses the main plugin bootstrap.
```

**Option 2: Manually re-sync critical files**
```bash
# FTP/SFTP: Ensure these files are current:
wp-content/mu-plugins/pearblog-engine/src/Core/Plugin.php
wp-content/mu-plugins/pearblog-engine/src/Monitoring/HealthController.php

# Then:
wp rewrite flush --hard
curl -i https://yoursite.com/wp-json/pearblog/v1/health
```

---

### Real-Time Stats Not Updating

**Check 1: Verify AJAX is enabled**
```bash
wp option get pearblog_v8_realtime_enabled
# Should return: 1
```

**Check 2: Test AJAX endpoint directly**
```bash
curl -X POST \
  -d "action=pb_v8_get_realtime_stats&nonce=YOUR_NONCE" \
  https://yoursite.com/wp-admin/admin-ajax.php
```

**Check 3: Verify JavaScript is loading**
- Open DevTools (F12)
- Go to Network tab
- Refresh the page
- Look for `admin-v8-enterprise.js` (should load with 200 status)

**Check 4: Enable debug mode to see errors**
```bash
wp option update pearblog_debug_mode 1
tail -f wp-content/pearblog-engine.log
```

---

## Comparison to Previous Versions

| Feature | v6 (Legacy) | v7 (SaaS) | v8 (Enterprise) |
|---------|-----------|-----------|-----------------|
| **Tabs** | 1 (Settings) | 10 tabs | 15 tabs |
| **Real-Time** | ❌ | ❌ | ✅ (5s polling) |
| **Dark Mode** | ❌ | ❌ | ✅ Persistent |
| **Theme** | Basic | Modern | Glassmorphism |
| **Languages** | EN only | EN only | EN + PL |
| **Health Endpoint** | REST API v1 | Improved | Full health checks |
| **AJAX Handlers** | Limited | Expanded | 4 handlers |
| **Design System** | WordPress admin | Custom UI | Enterprise grade |
| **Mobile Ready** | Limited | Partial | Desktop-first |

---

## FAQ

### Q: Can I switch back to v7?
**A:** Yes. Change `PEARBLOG_ADMIN_VERSION` to `'v7'` and refresh. No database migration needed.

### Q: Is dark mode saved?
**A:** Yes, preference is saved to `pearblog_v8_theme` option and persists across sessions.

### Q: Can I disable real-time updates?
**A:** Yes: `wp option update pearblog_v8_realtime_enabled 0`

### Q: What browsers are supported?
**A:** Chrome 90+, Firefox 88+, Safari 14+, Edge 90+. IE11 is not supported.

### Q: Can I customize the colors?
**A:** Yes, edit CSS variables in `assets/css/admin-v8-enterprise.css` or override with custom CSS.

### Q: Is multisite supported?
**A:** Yes, the Multisite/SaaS tab provides network management (requires `WP_ALLOW_MULTISITE`).

### Q: How do I export audit logs?
**A:** Go to **Security & Audit** tab (when fully implemented) and click "📥 Export".

---

## Version History

**v8.0.0** (2026-06-21)
- ✅ Initial release
- ✅ 15-tab admin interface
- ✅ Dark mode support
- ✅ Real-time analytics polling
- ✅ Health endpoint (comprehensive)
- ⚠️ Notifications system (placeholder)
- ⚠️ Advanced reporting export (placeholder)
- ⚠️ Audit logging (UI skeleton)

**Future (v8.1+)**
- Full notification queue system
- Complete audit logging with database persistence
- Full reporting with PDF/Excel export
- GraphQL API endpoint
- Webhook system
- Advanced security features

---

## Support & Resources

**Documentation:**
- [Main README](../README.md)
- [SETUP Guide](../SETUP.md)
- [Deployment Guide](../DEPLOYMENT.md)
- [API Documentation](../API-DOCUMENTATION.md)

**Troubleshooting:**
- [TROUBLESHOOTING.md](../TROUBLESHOOTING.md)
- [DEPLOYMENT-CHECKLIST.md](../DEPLOYMENT-CHECKLIST.md)

**Community:**
- Issues: https://github.com/AndyPearman89/PearBlog-Engine-/issues
- Discussions: https://github.com/AndyPearman89/PearBlog-Engine-/discussions

---

**License:** GPL-2.0-or-later  
**Author:** Andy Pearman  
**Repository:** https://github.com/AndyPearman89/PearBlog-Engine-

**Verification Note:** This documentation is verified against actual code in the repository (commit c96a721b8818f9e44061923d40018a9b9678b651). Features marked with ⚠️ are UI scaffolds that require implementation for production use.
