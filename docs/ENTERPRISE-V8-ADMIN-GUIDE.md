# Enterprise v8 Admin Guide

**PearBlog Engine v8.0.0 — Complete Enterprise Admin Interface**

The Enterprise v8 Admin is a comprehensive control center for managing all aspects of your PearBlog-powered site. It provides 15 specialized tabs, real-time analytics, and a modern glassmorphism UI designed to meet enterprise-grade operational requirements.

## Table of Contents

1. [Quick Start](#quick-start)
2. [Access & Setup](#access--setup)
3. [Interface Overview](#interface-overview)
4. [Tab Reference](#tab-reference)
5. [Features](#features)
6. [Technical Details](#technical-details)
7. [FAQ](#faq)

---

## Quick Start

### Accessing the Admin

Navigate to your WordPress admin panel:

```
/wp-admin/admin.php?page=pearblog-enterprise-v8
```

Or click **🚀 PearBlog v8** in the WordPress admin menu (top position).

### First-Time Setup

1. **Enable Enterprise v8** — Ensure `pearblog-engine.php` contains:
   ```php
   define( 'PEARBLOG_ADMIN_VERSION', 'v8-enterprise' );
   ```

2. **Verify Installation** — The admin interface should load with all 15 tabs visible

3. **Configure Settings** — Start with the **Settings Enterprise** tab to configure global options

4. **Explore Features** — Each tab provides specialized functionality for different aspects of content management

---

## Access & Setup

### Requirements

- **WordPress**: 5.8+
- **PHP**: 7.4+
- **PearBlog Engine**: 8.0.0+
- **User Role**: Administrator (`manage_options` capability)

### Browser Support

- Chrome/Edge 90+
- Firefox 88+
- Safari 14+
- Opera 76+

### Recommended Screen Resolution

- **Desktop**: 1920×1080 or higher
- **Tablet**: 1024×768 or higher
- **Not optimized for mobile** — the admin interface uses complex multi-column layouts and data-dense tables that require a minimum 1024px viewport width.

---

## Interface Overview

### Top Bar

The top bar provides quick access to global controls:

- **🍐 PearBlog Enterprise** — Branding and version badge (v8.0.0 MAX)
- **Language Toggle** (🇬🇧/🇵🇱) — Switch between English and Polish
- **Theme Toggle** (🌙/☀️) — Switch between dark and light modes
- **Notifications** (🔔) — Real-time notification center with unread badge
- **User Profile** — Current user avatar and info

### Tab Navigation

15 specialized tabs organized by function:

- **Core Operations**: Dashboard, Real-Time Analytics
- **Content & Strategy**: AI Strategy, Content Engine, SEO Advanced
- **Business**: Revenue Center, Leads & CRM
- **Automation & Analytics**: Automation Pro, Analytics Deep
- **Infrastructure**: Multisite/SaaS, Performance
- **Security & Admin**: Security & Audit, Advanced Reports, Integrations, Settings Enterprise

### Theme Support

**Light Mode** (default):
- Clean white backgrounds
- Purple accent colors (#667eea, #764ba2)
- High contrast for readability

**Dark Mode**:
- Dark backgrounds (#1a202c, #2d3748)
- Reduced eye strain
- Optimized for low-light environments
- Persistent preference (saved to `pearblog_v8_theme` option)

---

## Tab Reference

### 1. 🎯 Dashboard

**Purpose**: Enterprise-wide metrics and KPIs

**Features**:
- **Revenue Today** — Current day's revenue with % change
- **Active Users** — Real-time user count
- **Content Generated** — Articles published today
- **AI Cost** — Current AI API spending
- **Revenue Trend Chart** — 30-day revenue visualization (Chart.js)
- **Content Distribution** — Pie chart of content types
- **Recent Activity Feed** — Last 10 system events

**Use Cases**:
- Morning dashboard review
- Executive reporting
- Quick health check

---

### 2. 📊 Real-Time Analytics

**Purpose**: Live monitoring with 5-second updates

**Features**:
- **Live Visitors** — Current site visitors with sparkline chart
- **Revenue/Hour** — Hourly revenue rate
- **Conversions** — Real-time conversion tracking
- **Error Rate** — System error monitoring
- **Live Activity Stream** — Real-time event feed
- **WebSocket Support** — Auto-updates without page refresh

**Technical**:
- Updates via AJAX (`pb_v8_get_realtime_stats`)
- 5-second polling interval
- Can be disabled via `pearblog_v8_realtime_enabled` option

**Use Cases**:
- Launch day monitoring
- Campaign performance tracking
- System health monitoring

---

### 3. 🧠 AI Strategy

**Purpose**: AI-driven keyword discovery and content planning

**Features**:
- **Keyword Source Management**:
  - Manual — Add keywords manually via Queue tab
  - Automatic — AI discovers keywords based on industry
  - Competitive Scraping — Extract trending topics from competitors
  - Hybrid — Combine all three sources
- **Auto Discovery Settings**:
  - Enable/disable automatic keyword discovery
  - Daily discovery limit (1-100 keywords/day)
  - Keyword scraping toggle
- **Intent Prioritization**:
  - Balanced (default)
  - TOFU-focused (top of funnel)
  - MOFU-focused (middle of funnel)
  - BOFU-focused (bottom of funnel)
- **Discovery Schedule** — Configure cron frequency

**Configuration Options**:
- `pearblog_keyword_source` — manual|auto|scraping|hybrid
- `pearblog_intent_priority` — balanced|tofu|mofu|bofu
- `pearblog_auto_keyword_discovery` — boolean
- `pearblog_discovery_daily_limit` — 1-100
- `pearblog_keyword_scraping_enabled` — boolean

**Use Cases**:
- Setting up automated content pipeline
- Competitor analysis
- Keyword gap identification

---

### 4. ✍️ Content Engine

**Purpose**: Batch content generation and management

**Features**:
- **Batch Generation**:
  - Generate 1-100 articles at once
  - Publish immediately or save as drafts
  - Optional DALL-E featured image generation
  - Queue-based processing
- **Content Templates**:
  - Predefined article structures
  - Custom templates
  - Industry-specific formats
- **Rewrite/Update**:
  - Bulk content refresh
  - SEO optimization passes
  - Quality improvement

**Workflow**:
1. Ensure topics are in queue (via Automation tab)
2. Set batch count (1-100)
3. Choose publish/draft option
4. Enable image generation if needed
5. Click "Start Batch Generation"
6. Monitor progress in Activity Feed

**Use Cases**:
- Monthly content creation sprint
- Site launch content production
- Seasonal content updates

---

### 5. 🔍 SEO Advanced

**Purpose**: SEO optimization and meta management

**Features**:
- Meta title/description optimization
- Schema.org structured data
- XML sitemap management
- Keyword tracking and rankings
- Internal linking suggestions
- SEO audit reports

**Integration**:
- Works with SEO V3 CLI commands
- Supports Google Search Console API
- Schema Manager integration

**Use Cases**:
- On-page SEO optimization
- Technical SEO audits
- Search ranking monitoring

---

### 6. 💰 Revenue Center

**Purpose**: Monetization and revenue management

**Features**:
- **AdSense Configuration**:
  - Publisher ID management
  - Enable/disable AdSense
  - Strategy selection (aggressive/balanced/conservative/funnel_aware)
  - Per-stage control (TOFU/MOFU/BOFU)
- **Revenue Analytics**:
  - Daily/weekly/monthly revenue charts
  - RPM (revenue per thousand)
  - Ad placement performance
- **Funnel-Aware Monetization**:
  - Light ads for TOFU (awareness stage)
  - Moderate ads for MOFU (consideration stage)
  - Aggressive ads for BOFU (decision stage)

**Configuration**:
```bash
wp option update pearblog_adsense_publisher_id "ca-pub-XXXXXXXXX"
wp option update pearblog_adsense_enabled 1
wp option update pearblog_adsense_strategy "funnel_aware"
wp option update pearblog_adsense_enable_tofu 1
wp option update pearblog_adsense_enable_mofu 1
wp option update pearblog_adsense_enable_bofu 1
```

**Use Cases**:
- AdSense setup and configuration
- Revenue optimization
- A/B testing ad strategies

---

### 7. 👥 Leads & CRM

**Purpose**: Lead management and conversion tracking

**Features**:
- Lead capture from Landing V5 forms
- Custom database table queries (`{$wpdb->prefix}poradnik_leads`)
- Lead scoring and qualification
- Conversion funnel analysis
- Export to CSV/Excel

**Database Schema**:
```sql
CREATE TABLE wp_poradnik_leads (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  name varchar(255) NOT NULL,
  email varchar(255) NOT NULL,
  phone varchar(50),
  service varchar(255),
  city varchar(100),
  message text,
  source varchar(255),
  created_at datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id)
);
```

**Use Cases**:
- Lead qualification
- Sales pipeline management
- Marketing attribution

---

### 8. ⚙️ Automation Pro

**Purpose**: Workflow automation and scheduling

**Features**:
- Cron job management
- Batch operations scheduling
- Content pipeline automation
- Auto-publishing workflows
- Webhook integrations

**Available Cron Jobs**:
- `pearblog_pipeline_cron` — Content generation pipeline
- `pearblog_topic_research_refresh` — Weekly topic research
- `pearblog_publish_schedule_refresh` — Weekly publish analysis
- `pearblog_content_refresh` — Monthly content updates

**Use Cases**:
- Set-it-and-forget-it content production
- Scheduled content updates
- Automated maintenance tasks

---

### 9. 📈 Analytics Deep

**Purpose**: Deep-dive analytics and insights

**Features**:
- GA4 integration
- Page engagement metrics
- User journey analysis
- Conversion tracking
- Custom event tracking
- Cohort analysis

**Metrics**:
- Sessions, engaged sessions, bounce rate
- Average session duration
- Pages per session
- Goal completions
- E-commerce transactions (if enabled)

**Use Cases**:
- Monthly analytics review
- Content performance analysis
- User behavior insights

---

### 10. 🌐 Multisite/SaaS

**Purpose**: Multi-tenant management for WordPress Multisite

**Features**:
- Site provisioning (create/delete subsites)
- Network-wide settings
- Tenant context switching
- Per-site configuration
- Bulk operations across sites

**Tenant Context**:
```php
use PearBlogEngine\Tenant\TenantContext;

$context = TenantContext::for_site(2);
$industry = $context->get_industry(); // e.g., "poradnik"
```

**Use Cases**:
- SaaS platform management
- Agency client management
- Multi-site content networks

---

### 11. ⚡ Performance

**Purpose**: Performance monitoring and optimization

**Features**:
- Database query analysis
- Slow query detection
- Caching metrics (Redis/Memcached)
- PHP memory usage
- Response time tracking
- Health checks (`/wp-json/pearblog/v1/health`)

**Performance Metrics**:
- Page load time (TTFB, FCP, LCP)
- Database query count per request
- Cache hit ratio
- Memory peak usage
- API response times

**Use Cases**:
- Performance debugging
- Optimization opportunities
- Resource planning

---

### 12. 🔒 Security & Audit

**Purpose**: Security scoring and audit logging

**Features**:
- **Security Score**: 0-100 rating (dynamically calculated based on current configuration)
- **Failed Login Tracking**: Monitor suspicious login attempts
- **Blocked IP Addresses**: Auto-block after failed attempts
- **Audit Log**: All admin actions with timestamp, user, action, IP
- **Export**: Download audit logs as CSV for compliance

**Audit Log Columns**:
- Timestamp (YYYY-MM-DD HH:MM:SS)
- User (username)
- Action (e.g., "Settings Updated", "Content Generated")
- IP Address
- Status (success/failed)

**Use Cases**:
- Security audits
- Compliance reporting (GDPR, SOC 2)
- Intrusion detection

---

### 13. 📋 Advanced Reports

**Purpose**: Comprehensive reporting with export

**Features**:
- **Revenue Report** — Detailed revenue analysis
- **Content Performance** — Top articles, engagement, traffic
- **SEO Report** — Rankings, impressions, CTR
- **AI Cost Analysis** — API usage and cost breakdown

**Export Formats**:
- CSV (UTF-8 with BOM for Excel)
- PDF (formatted reports)
- JSON (API consumption)
- Excel (XLSX with charts)

**Use Cases**:
- Monthly executive reports
- Client reporting
- Historical analysis

---

### 14. 🔗 Integrations

**Purpose**: Third-party API management

**Features**:
- **Google Analytics** — GA4 property connection
- **Google AdSense** — Publisher account linkage
- **Google Search Console** — Site verification and data access
- **Mailchimp** — Email list synchronization
- **API Status** — Monitor all connected services

**Connection Status**:
- ✅ Active (green badge)
- ⚠️ Pending (yellow badge)
- ❌ Failed (red badge)

**Use Cases**:
- Initial platform setup
- Integration troubleshooting
- API health monitoring

---

### 15. ⚙️ Settings Enterprise

**Purpose**: System-wide configuration and options

**Features**:
- Global plugin settings
- AI provider configuration (OpenAI, Anthropic, Cohere)
- Database settings
- Email notifications
- Debug mode toggle
- Feature flags

**Key Options**:
- `pearblog_ai_provider` — openai|anthropic|cohere
- `pearblog_ai_model` — Model ID for content generation
- `pearblog_enable_image_generation` — boolean
- `pearblog_homepage_version` — v3|v7
- `pearblog_admin_version` — v6|v7|v8-enterprise

**Use Cases**:
- Initial configuration
- Provider switching
- Feature enablement

---

## Features

### Real-Time Updates

The Enterprise v8 admin uses AJAX polling (5-second intervals) to provide real-time updates without page refresh:

```javascript
// Automatic refresh every 5 seconds
setInterval(function() {
  pbV8RefreshRealtimeStats();
}, 5000);
```

### Dark Mode

Toggle between light and dark themes with persistent preference:

```javascript
function pbV8ToggleTheme() {
  jQuery.post(pbV8Data.ajaxUrl, {
    action: 'pb_v8_toggle_theme',
    nonce: pbV8Data.nonce
  }, function(response) {
    location.reload(); // Reload to apply new theme
  });
}
```

### Language Switching

Support for English and Polish with full i18n:

```php
$translations = [
  'en' => [ 'loading' => 'Loading...', 'save' => 'Save Changes' ],
  'pl' => [ 'loading' => 'Ładowanie...', 'save' => 'Zapisz Zmiany' ]
];
```

### Notifications Center

Real-time notification system with categories:

- **Success** (green) — Successful operations
- **Warning** (yellow) — Warnings and alerts
- **Error** (red) — Failed operations
- **Info** (blue) — Informational messages

---

## Technical Details

### File Structure

```
mu-plugins/pearblog-engine/
├── src/Admin/
│   ├── AdminPageV8Enterprise.php    # Main admin class
│   ├── DashboardTab.php             # Dashboard tab
│   ├── RealtimeAnalyticsTab.php     # Real-Time Analytics tab
│   ├── StrategyTab.php              # AI Strategy tab
│   ├── ContentEngineTab.php         # Content Engine tab
│   ├── SEOTab.php                   # SEO Advanced tab
│   ├── MonetizationTab.php          # Revenue Center tab
│   ├── LeadsTab.php                 # Leads & CRM tab
│   ├── AutomationTab.php            # Automation Pro tab
│   ├── AnalyticsTab.php             # Analytics Deep tab
│   ├── MultisiteTab.php             # Multisite/SaaS tab
│   ├── PerformanceDashboardTab.php  # Performance tab
│   ├── SecurityAuditTab.php         # Security & Audit tab
│   ├── AdvancedReportsTab.php       # Advanced Reports tab
│   ├── IntegrationsTab.php          # Integrations tab
│   └── SettingsTab.php              # Settings Enterprise tab
├── assets/
│   ├── css/admin-v8-enterprise.css  # Styles (18.5 KB)
│   └── js/admin-v8-enterprise.js    # Scripts (12.4 KB)
└── pearblog-engine.php              # Plugin bootstrap
```

### PHP Classes

**Main Class**: `PearBlogEngine\Admin\AdminPageV8Enterprise`

**Methods**:
- `register()` — Hook into WordPress
- `add_menu()` — Register admin menu
- `render_page()` — Output main HTML
- `render_tab_panel($tab_id)` — Route to tab renderer
- `enqueue_assets($hook)` — Load CSS/JS
- `ajax_get_realtime_stats()` — Real-time data endpoint
- `ajax_get_notifications()` — Notifications endpoint
- `ajax_toggle_theme()` — Theme switcher endpoint

**Tab Integration**:
```php
case 'strategy':
  StrategyTab::render();
  break;
```

### JavaScript API

**Global Object**: `pbV8Data`

```javascript
{
  ajaxUrl: '/wp-admin/admin-ajax.php',
  nonce: 'abc123...',
  currentTab: 'dashboard',
  theme: 'light',
  language: 'en',
  realtimeEnabled: true,
  version: '8.0.0',
  translations: { /* i18n strings */ }
}
```

**Functions**:
- `pbV8SwitchTab(tabId)` — Change active tab
- `pbV8ToggleTheme()` — Switch light/dark
- `pbV8ToggleLanguage()` — Switch EN/PL
- `pbV8ToggleNotifications()` — Open/close notification center
- `pbV8RefreshActivity()` — Reload activity feed
- `pbV8InitRealtime()` — Start real-time monitoring
- `pbV8GenerateReport(type)` — Generate report
- `pbV8Export(format)` — Export data

### CSS Variables

```css
:root {
  --pb-v8-primary: #667eea;
  --pb-v8-secondary: #764ba2;
  --pb-v8-success: #10b981;
  --pb-v8-warning: #f59e0b;
  --pb-v8-danger: #ef4444;
  --pb-v8-bg: #ffffff;
  --pb-v8-text: #1f2937;
  --pb-v8-border: #e5e7eb;
  --pb-v8-space-sm: 8px;
  --pb-v8-space-md: 16px;
  --pb-v8-space-lg: 24px;
  --pb-v8-space-xl: 32px;
}

[data-theme="dark"] {
  --pb-v8-bg: #1a202c;
  --pb-v8-text: #f7fafc;
  --pb-v8-border: #2d3748;
}
```

### WordPress Hooks

**Actions**:
- `admin_menu` — Register menu page
- `admin_init` — Register settings
- `admin_enqueue_scripts` — Load assets
- `wp_ajax_pb_v8_get_realtime_stats` — Real-time data
- `wp_ajax_pb_v8_get_notifications` — Notifications
- `wp_ajax_pb_v8_toggle_theme` — Theme switch
- `wp_ajax_pb_v8_export_report` — Report export

**Options**:
- `pearblog_v8_theme` — light|dark
- `pearblog_v8_language` — en|pl
- `pearblog_v8_realtime_enabled` — boolean

### External Dependencies

**Chart.js 4.4.0**:
```html
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
```

**Alpine.js 3.13.3**:
```html
<script src="https://cdn.jsdelivr.net/npm/alpinejs@3.13.3/dist/cdn.min.js"></script>
```

> **Security Note for Enterprise Deployments**: When loading Chart.js and Alpine.js from a CDN in production, use Subresource Integrity (SRI) hashes to prevent supply-chain attacks (e.g., add `integrity="sha384-..."` and `crossorigin="anonymous"` attributes to each `<script>` tag). Alternatively, bundle these libraries locally and serve them from your own infrastructure to eliminate the external dependency entirely.

---

## FAQ

### How do I enable Enterprise v8 admin?

Add this to `pearblog-engine.php` (already done in v8.0.0):
```php
define( 'PEARBLOG_ADMIN_VERSION', 'v8-enterprise' );
```

### Can I switch back to v7 admin?

Yes, change the constant to:
```php
define( 'PEARBLOG_ADMIN_VERSION', 'v7' );
```

### Is dark mode persistent?

Yes, theme preference is saved to `pearblog_v8_theme` option and persists across sessions.

### How do I disable real-time updates?

Set option to false:
```bash
wp option update pearblog_v8_realtime_enabled 0
```

### What browsers are supported?

Modern browsers (Chrome 90+, Firefox 88+, Safari 14+, Edge 90+). IE11 is not supported.

### Can I customize the colors?

Yes, edit CSS variables in `assets/css/admin-v8-enterprise.css` or override with child theme.

### How do I add a custom tab?

1. Create a new tab class in `src/Admin/YourTab.php`
2. Add static `render()` method
3. Add to `TABS` constant in `AdminPageV8Enterprise`
4. Add case in `render_tab_panel()`
5. Add use statement at top

### Is multisite supported?

Yes, the Multisite/SaaS tab provides full network management capabilities.

### How do I export audit logs?

Go to **Security & Audit** tab and click "📥 Export" button. Logs download as CSV.

### Can I integrate with my own APIs?

Yes, use the **Integrations** tab or create custom webhook handlers in **Automation Pro** tab.

---

## Support

**Documentation**: https://github.com/AndyPearman89/PearBlog-Engine-/tree/main/docs

**Issues**: https://github.com/AndyPearman89/PearBlog-Engine-/issues

**Version**: 8.0.0 (2026-05-04)

**License**: GPL-2.0-or-later

---

🤖 Generated with [Claude Code](https://claude.com/claude-code)
