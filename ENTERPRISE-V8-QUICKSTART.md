# Enterprise V8 Quick Start Guide

## Welcome to PearBlog Engine - Enterprise V8

This guide will get you up and running with the complete Enterprise V8 system in **under 30 minutes**.

---

## What You're Getting

**Enterprise V8** combines three powerful systems:

1. **🎯 Enterprise Admin Dashboard** - 15 specialized tabs for complete control
2. **👥 PT24 AI Lead Engine V2** - Intelligent lead management with DDD architecture
3. **✍️ Poradnik Engine V2** - Revenue-focused content optimization

**Total Power**: 31 PHP files, 9 database tables, 13 REST APIs, 15 admin tabs

---

## 5-Minute Installation

### Step 1: Upload Files (1 minute)
```bash
# Upload plugin to must-use plugins directory
cd /path/to/wordpress/wp-content/mu-plugins/
git clone https://github.com/AndyPearman89/PearBlog-Engine- pearblog-engine
```

### Step 2: Enable Enterprise Mode (1 minute)

**Option A – Include the ready-made config snippet** (recommended):
```php
// Add this line to wp-config.php (before "That's all, stop editing!"):
require_once __DIR__ . '/wp-content/mu-plugins/pearblog-engine/config/wp-config-pearblog-v8.php';
```

> See `mu-plugins/pearblog-engine/config/wp-config-pearblog-v8-sample.php`
> for a fully-commented example of all available constants.

**Option B – Manual define**:
```bash
# Edit the main plugin file
nano pearblog-engine/pearblog-engine.php

# Add this line after line 23:
define( 'PEARBLOG_ADMIN_VERSION', 'v8-enterprise' );

# Save and exit (Ctrl+X, Y, Enter)
```

### Step 3: Database Setup (2 minutes)
```bash
# Install WordPress CLI if not already installed
# Then run:
wp plugin activate pearblog-engine
```

The plugin will automatically create 9 database tables:
- `wp_pearblog_leads` - Lead storage
- `wp_pearblog_lead_events` - Activity tracking
- `wp_pearblog_lead_notifications` - Notification queue
- `wp_pearblog_lead_analytics` - Lead analytics
- `wp_pearblog_articles` - Article registry
- `wp_pearblog_article_stats` - Performance stats
- `wp_pearblog_events` - Event tracking
- `wp_pearblog_ab_tests` - A/B testing
- `wp_pearblog_service_data` - PT24 market data

### Step 4: Configure API Keys (1 minute)
```bash
# Log in to WordPress admin
# Navigate to: PearBlog Engine → Settings Enterprise
# Enter your API keys:
```

**Required Keys**:
- ✅ OpenAI API Key (for AI content & replies)
- ✅ Google Search Console API (for SEO data)

**Optional Keys** (for notifications):
- Twilio Account SID & Auth Token (for SMS)
- OR SMSApi.pl API Token (for SMS)

---

## First Login - What You'll See

### Access the Dashboard
1. Log in to WordPress admin
2. Look for **PearBlog Engine** in the left sidebar
3. Click to open Enterprise V8 dashboard

### Dashboard Overview
You'll see **15 specialized tabs**:

| Tab | Purpose | Key Features |
|-----|---------|--------------|
| 🎯 Dashboard Enterprise | Executive overview | KPIs, activity timeline, quick actions |
| 📊 Real-Time Analytics | Live data | WebSocket updates, real-time metrics |
| 🧠 AI Strategy | Engine controls | PT24 & Poradnik configuration |
| ✍️ Content Engine | Article management | Score, optimize, A/B test |
| 🔍 SEO Advanced | SEO insights | GSC integration, ranking tracking |
| 💰 Revenue Center | Revenue tracking | PT24 commissions, attribution |
| 👥 Leads & CRM | Lead management | Score, route, reply automation |
| ⚙️ Automation Pro | Workflow automation | Background workers, scheduling |
| 📈 Analytics Deep | In-depth analytics | Custom reports, trends |
| 🌐 Multisite/SaaS | Multi-tenant | Cross-site management |
| ⚡ Performance | Speed optimization | Cache, CDN, optimization |
| 🔒 Security & Audit | Security logs | Access logs, compliance |
| 📋 Advanced Reports | Custom reporting | Export, scheduled reports |
| 🔗 Integrations | Third-party tools | API connections, webhooks |
| ⚙️ Settings Enterprise | Configuration | System-wide settings |

---

## Your First Lead (5 minutes)

### Create a Test Lead via API

**Option A: Using curl**
```bash
curl -X POST https://your-site.com/wp-json/pearblog/v1/leads \
  -H "Content-Type: application/json" \
  -d '{
    "intent": "REPAIR",
    "package": "PREMIUM",
    "contact": {
      "name": "Jan Kowalski",
      "phone": "+48123456789",
      "email": "jan.kowalski@example.com",
      "city": "Warszawa"
    },
    "message": "Pilne! Piec centralny przestał działać. Proszę o szybki kontakt.",
    "metadata": {
      "source": "test_quick_start"
    }
  }'
```

**Option B: Using WordPress admin**
1. Go to **👥 Leads & CRM** tab
2. Click **+ New Lead** button
3. Fill in the form
4. Click **Create Lead**

### What Happens Next (Automatic)
1. ✅ Lead created in database
2. ✅ Score calculated (0-100 based on urgency, budget, clarity, location, demand)
3. ✅ SLA deadline set (30min for PREMIUM, 2h for STANDARD, none for BASIC)
4. ✅ AI reply generated and sent (if auto-reply enabled)
5. ✅ Lead routed to appropriate tier (Expert/Pro/Auto-reply)
6. ✅ Notifications sent to assigned person

### View Your Lead
1. Go to **👥 Leads & CRM** tab
2. You'll see your lead in the table
3. Click on it to see:
   - Lead score breakdown
   - Activity timeline
   - Auto-generated AI reply
   - Routing decision

**Example Score Breakdown**:
- Urgency: 30/30 (keyword "pilne" detected)
- Budget: 20/25 (implied medium budget)
- Clarity: 18/20 (clear message, full contact info)
- Location: 15/15 (major city - Warszawa)
- Demand: 5/10 (heating repair - medium demand)
- **Total: 88/100** → Routes to **Expert** tier

---

## Your First Article (10 minutes)

### Generate Content via API

**Option A: Using curl**
```bash
curl -X POST https://your-site.com/wp-json/pearblog/v1/content/generate \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_WP_TOKEN" \
  -d '{
    "topic": "remont-lazienki",
    "city": "krakow",
    "service": "kompleksowy remont łazienki",
    "auto_publish": false
  }'
```

**Option B: Using WordPress admin**
1. Go to **✍️ Content Engine** tab
2. Click **+ Generate Content** button
3. Fill in:
   - Topic: `remont-lazienki`
   - City: `krakow`
   - Service: `kompleksowy remont łazienki`
4. Click **Generate**

### What Happens Next (Background)
1. ✅ Article queued for generation
2. ✅ Background worker picks up task (within 60 seconds)
3. ✅ AI generates full article (~2,000 words)
4. ✅ PT24 marketplace data scraped for pricing
5. ✅ WordPress post created in draft status
6. ✅ Initial score calculated

### Article Content Structure
The AI generates a comprehensive article with:
- **Intro** - Hook and overview
- **Ile kosztuje?** - Cost analysis (PT24 data)
- **Od czego zależy cena?** - Price factors
- **Jak wybrać wykonawcę?** - Selection advice
- **Soft CTA** - Natural PT24 link
- **FAQ** - Common questions

### View Your Article
1. Go to **✍️ Content Engine** tab
2. Find your article in the list
3. Click to see:
   - Performance score (0-100)
   - Category (SCALE/BOOST/OPTIMIZE/DELETE)
   - Metrics (views, CTR, revenue)
   - Optimization suggestions

**Example Score Breakdown**:
- SEO Score: 12/20 (20% weight) - Needs SEO data from GSC
- Engagement: 15/20 (20% weight) - Needs user interaction data
- CTR: 16/20 (20% weight) - CTA click rate
- Revenue: 8/40 (40% weight) - Needs conversion data
- **Total: 51/100** → Category: **OPTIMIZE**

---

## Understanding the Scoring Systems

### PT24 Lead Scoring (0-100)
```
Score = Urgency (30%) + Budget (25%) + Clarity (20%) + Location (15%) + Demand (10%)
```

**Routing Rules**:
- **≥80**: Expert tier (high-value leads)
- **50-79**: Pro tier (qualified leads)
- **<50**: Auto-reply (low-priority)

**SLA Deadlines**:
- **Premium**: 30 minutes
- **Standard**: 2 hours
- **Basic**: No SLA

### Poradnik Content Scoring (0-100)
```
Score = (SEO × 0.2) + (Engagement × 0.2) + (CTR × 0.2) + (Revenue × 0.4)
```

**Categories**:
- **SCALE** (≥80): High performers → Duplicate & scale
- **BOOST** (60-79): Good potential → SEO boost
- **OPTIMIZE** (40-59): Underperforming → Content optimization
- **DELETE** (<40): Poor performers → Consider removal

**Decision Rules**:
1. Low CTR (<5%) → Rewrite CTA
2. Low Engagement (<40s) → Rewrite intro
3. No Revenue + Traffic → Add CTA
4. Low SEO CTR (<2%) → Rewrite meta
5. High Bounce (>70%) → Improve intro

---

## Key Features Tour

### 1. Real-Time Analytics (📊 Tab)
**What it shows**:
- Live lead count (updates via WebSocket)
- Real-time revenue tracking
- Active users on your articles
- Conversion funnel visualization

**How to use**:
1. Click **📊 Real-Time Analytics** tab
2. Watch metrics update in real-time
3. Click on any metric for details
4. Export data with **Export** button

### 2. AI Strategy (🧠 Tab)
**What it controls**:
- PT24 Lead Engine settings (scoring weights, SLA, routing)
- Poradnik Engine settings (scoring formula, decision thresholds)
- AI behavior (tone, style, creativity)
- Automation rules

**How to use**:
1. Click **🧠 AI Strategy** tab
2. Adjust sliders for scoring weights
3. Set SLA deadlines per package
4. Configure routing thresholds
5. Save changes

**Example Configuration**:
```yaml
PT24 Lead Engine:
  Urgency Weight: 30%
  Budget Weight: 25%
  SLA Premium: 30 minutes
  Expert Threshold: 80+

Poradnik Engine:
  SEO Weight: 20%
  Revenue Weight: 40%
  SCALE Threshold: 80+
  Delete Threshold: 40-
```

### 3. Leads & CRM (👥 Tab)
**What it shows**:
- All leads in one table
- Filterable by state, score, package, date
- Searchable by name, email, phone
- Bulk actions available

**How to use**:
1. Click **👥 Leads & CRM** tab
2. View lead list (newest first)
3. Click any lead for details
4. Take quick actions:
   - **Reply** - Generate AI reply
   - **Route** - Assign to expert
   - **Escalate** - Priority escalation
   - **Archive** - Mark as complete

**Lead States**:
- 🟢 **NEW** - Just received
- 🟡 **CONTACTED** - Reply sent
- 🔵 **QUALIFIED** - Expert engaged
- 🟢 **CONVERTED** - Sale completed
- ⚫ **DEAD** - Lost/unresponsive

### 4. Content Engine (✍️ Tab)
**What it shows**:
- All articles with performance scores
- Color-coded categories
- Quick filters (SCALE/BOOST/OPTIMIZE/DELETE)
- Bulk optimization tools

**How to use**:
1. Click **✍️ Content Engine** tab
2. View article list (sorted by score)
3. Click any article for details
4. Take actions:
   - **Optimize** - Run AI optimization
   - **A/B Test** - Create test variant
   - **Re-score** - Recalculate score
   - **Delete** - Remove article

**Score Color Coding**:
- 🟢 **Green** (80-100): SCALE - Excellent performance
- 🟡 **Yellow** (60-79): BOOST - Good potential
- 🟠 **Orange** (40-59): OPTIMIZE - Needs work
- 🔴 **Red** (0-39): DELETE - Poor performance

### 5. Revenue Center (💰 Tab)
**What it shows**:
- Total revenue (all sources)
- Revenue by article
- Revenue by traffic source
- Lead-to-revenue attribution
- PT24 affiliate commissions

**How to use**:
1. Click **💰 Revenue Center** tab
2. View revenue chart (daily/weekly/monthly)
3. See top performing articles
4. Check attribution (which articles drive sales)
5. Export revenue reports

---

## Automation Setup

### Background Workers
Enterprise V8 includes 4 automated workers:

| Worker | Schedule | Purpose |
|--------|----------|---------|
| `poradnik_generate_worker` | On-demand | Content generation |
| `poradnik_scoring_worker` | Daily 05:00 | Recalculate all scores |
| `poradnik_optimize_worker` | Weekly Sun 01:00 | Optimize underperforming content |
| `poradnik_aggregate_stats` | Hourly | Aggregate event data |

### Verify Workers Active
```bash
# Check scheduled events
wp cron event list | grep poradnik

# Expected output:
# poradnik_scoring_worker    2026-05-04 05:00:00  daily
# poradnik_optimize_worker   2026-05-05 01:00:00  weekly
# poradnik_aggregate_stats   2026-05-03 22:00:00  hourly
```

### Manual Trigger (for testing)
```bash
# Run scoring worker now
wp cron event run poradnik_scoring_worker

# Run optimization worker now
wp cron event run poradnik_optimize_worker

# Run stats aggregation now
wp cron event run poradnik_aggregate_stats
```

---

## Event Tracking Integration

### Add JavaScript Tracker to Theme
Add this to your theme's footer (or use Google Tag Manager):

```javascript
<script>
(function() {
  const trackEvent = (eventType, data = {}) => {
    fetch('/wp-json/pearblog/v1/event', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        event_type: eventType,
        article_id: window.pearblogArticleId,
        post_id: window.pearblogPostId,
        session_id: window.pearblogSessionId,
        ...data
      })
    });
  };

  // Track page view
  trackEvent('view', {
    referrer: document.referrer,
    utm_source: new URLSearchParams(window.location.search).get('utm_source'),
    utm_medium: new URLSearchParams(window.location.search).get('utm_medium')
  });

  // Track scroll depth
  let maxScroll = 0;
  window.addEventListener('scroll', () => {
    const scrollPercent = Math.round((window.scrollY / (document.body.scrollHeight - window.innerHeight)) * 100);
    if (scrollPercent > maxScroll) {
      maxScroll = scrollPercent;
      if ([20, 50, 80, 100].includes(scrollPercent)) {
        trackEvent('scroll', { scroll_depth: scrollPercent });
      }
    }
  });

  // Track CTA clicks
  document.querySelectorAll('a[href*="pt24.pl"]').forEach(link => {
    link.addEventListener('click', () => {
      trackEvent('cta_click', {
        event_data: { cta_text: link.textContent, cta_href: link.href }
      });
    });
  });

  // Track time on page (send on page unload)
  const startTime = Date.now();
  window.addEventListener('beforeunload', () => {
    const timeSeconds = Math.round((Date.now() - startTime) / 1000);
    navigator.sendBeacon('/wp-json/pearblog/v1/event', JSON.stringify({
      event_type: 'scroll',
      article_id: window.pearblogArticleId,
      post_id: window.pearblogPostId,
      session_id: window.pearblogSessionId,
      event_data: { time_seconds: timeSeconds }
    }));
  });
})();
</script>
```

### Set Global Variables in Theme
Add this to your article template (e.g., `single.php`):

```php
<script>
window.pearblogArticleId = <?php echo get_post_meta( get_the_ID(), 'pearblog_article_id', true ) ?: 'null'; ?>;
window.pearblogPostId = <?php the_ID(); ?>;
window.pearblogSessionId = '<?php echo wp_get_session_token() ?: 'guest_' . uniqid(); ?>';
</script>
```

---

## Common Workflows

### Workflow 1: Handling High-Score Leads
1. **Notification arrives** (email/SMS): "New lead scored 87"
2. **Open dashboard** → **👥 Leads & CRM** tab
3. **Click lead** to view details
4. **Review AI-generated reply** (already sent automatically)
5. **Assign to expert** (if not already routed)
6. **Expert receives notification** and follows up
7. **Update lead state** as conversation progresses:
   - NEW → CONTACTED → QUALIFIED → CONVERTED

### Workflow 2: Optimizing Low-Performing Content
1. **Weekly report arrives**: "5 articles in DELETE category"
2. **Open dashboard** → **✍️ Content Engine** tab
3. **Filter by DELETE** (score <40)
4. **Click article** to view details
5. **Review optimization suggestions**:
   - "Low CTR: Rewrite CTA"
   - "High bounce: Improve intro"
   - "No revenue: Add PT24 links"
6. **Click "Optimize Now"** to run AI optimization
7. **Create A/B test** to validate improvements
8. **Monitor for 7 days** to see score improvement

### Workflow 3: Scaling High-Performing Content
1. **Daily report arrives**: "Article #456 scored 92 (SCALE)"
2. **Open dashboard** → **✍️ Content Engine** tab
3. **Click top-scoring article**
4. **Review performance**:
   - Views: 2,450/day
   - Revenue: 850 PLN/day
   - CTR: 12.3%
5. **Decision**: Scale to other cities
6. **Generate variants**:
   - Original: "Remont łazienki Kraków"
   - Variant 1: "Remont łazienki Warszawa"
   - Variant 2: "Remont łazienki Wrocław"
   - Variant 3: "Remont łazienki Gdańsk"
7. **Monitor all variants** for 30 days
8. **Expected result**: 4x revenue from scaled content

### Workflow 4: A/B Testing CTAs
1. **Article in BOOST category** (score 73)
2. **Hypothesis**: Better CTA will improve conversions
3. **Create A/B test**:
   - Variant A: "Sprawdź oferty w Krakowie"
   - Variant B: "Zobacz bezpłatne wyceny od fachowców"
4. **Split traffic 50/50**
5. **Wait for 100+ views per variant**
6. **Check statistical significance**:
   - Z-score > 1.96 = significant
7. **Apply winner automatically**
8. **Re-score article** with new CTR data

---

## Troubleshooting

### Problem: Dashboard shows "Enterprise mode not enabled"
**Solution**:
```bash
# Check the constant
grep "PEARBLOG_ADMIN_VERSION" mu-plugins/pearblog-engine/pearblog-engine.php

# Should show:
# define( 'PEARBLOG_ADMIN_VERSION', 'v8-enterprise' );

# If not, add it after line 23 and save
```

### Problem: Leads not appearing
**Solution**:
```bash
# Check database table exists
wp db query "SHOW TABLES LIKE 'wp_pearblog_leads';"

# If empty, reinstall schema
wp eval "\\PearBlogEngine\\LeadAI\\Infrastructure\\LeadAISchema::install();"
```

### Problem: Content generation not working
**Solution**:
1. Check OpenAI API key configured (Settings Enterprise tab)
2. Verify WP-Cron is running: `wp cron test`
3. Manually trigger worker: `wp cron event run poradnik_generate_worker`
4. Check error log: `tail -f wp-content/debug.log`

### Problem: Events not tracking
**Solution**:
1. Verify JavaScript tracker added to theme
2. Check global variables set (`window.pearblogArticleId`)
3. Test event endpoint:
```bash
curl -X POST https://your-site.com/wp-json/pearblog/v1/event \
  -H "Content-Type: application/json" \
  -d '{"event_type": "view", "article_id": 1}'
```
4. Check database: `SELECT COUNT(*) FROM wp_pearblog_events;`

### Problem: Scores not calculating
**Solution**:
1. Ensure article has stats: `SELECT * FROM wp_pearblog_article_stats WHERE article_id = 456;`
2. Run aggregation: `wp cron event run poradnik_aggregate_stats`
3. Recalculate score: `wp eval "do_action('pearblog_calculate_scores');"`

---

## Next Steps

### Week 1: Foundation
- [x] Install and configure Enterprise V8
- [x] Set up API keys (OpenAI, GSC)
- [x] Create first test lead
- [x] Generate first test article
- [x] Add event tracking to theme
- [x] Verify background workers running

### Week 2: Content Production
- [x] Generate 10-20 articles
- [x] Monitor initial performance
- [x] Review scoring and categories
- [x] Optimize BOOST/OPTIMIZE articles
- [x] Start first A/B test

### Week 3: Lead Management
- [x] Integrate PT24 landing pages with Lead Engine
- [x] Configure SLA deadlines
- [x] Set up routing rules
- [x] Train team on lead handling
- [x] Monitor conversion rates

### Week 4: Optimization
- [x] Review weekly performance reports
- [x] Scale top-performing content (SCALE category)
- [x] Optimize underperforming content (OPTIMIZE category)
- [x] Archive poor performers (DELETE category)
- [x] Fine-tune scoring weights based on data

### Month 2+: Growth
- [x] Expand content to new cities/topics
- [x] Implement advanced automation rules
- [x] Build custom reports for stakeholders
- [x] Integrate additional data sources
- [x] Train AI models on your specific data

---

## Getting Help

### Documentation
- **Implementation Guides**:
  - `PT24-LEADAI-IMPLEMENTATION.md` - Lead Engine technical docs
  - `PORADNIK-IMPLEMENTATION.md` - Poradnik Engine technical docs
- **Overview Docs**:
  - `ENTERPRISE-V8-COMPLETE-STATUS.md` - System overview
  - `ENTERPRISE-V8-STEP-BY-STEP.md` - Chronological build guide
- **Testing**:
  - `ENTERPRISE-V8-INTEGRATION-TESTS.md` - Complete test suite

### Support Channels
- **GitHub Issues**: https://github.com/AndyPearman89/PearBlog-Engine-/issues
- **Documentation**: In repository root (*.md files)

### Emergency Contacts
If you encounter critical issues:
1. Check error logs: `wp-content/debug.log`
2. Review troubleshooting section above
3. Run health check: `wp pearblog health`
4. Contact support with error details

---

## Success Metrics

Track these KPIs to measure Enterprise V8 performance:

### Lead Engine Metrics
- **Lead Volume**: Leads per day
- **Average Lead Score**: Target >70
- **Conversion Rate**: Target >20%
- **SLA Compliance**: Target >95%
- **Response Time**: Target <30min for Premium

### Content Engine Metrics
- **Content Score**: Average across all articles, target >65
- **SCALE Articles**: Target >20% of portfolio
- **Revenue per Article**: Target >500 PLN/month
- **CTR**: Target >8%
- **Engagement**: Target >2 minutes average

### System Metrics
- **API Response Time**: Target <500ms
- **Uptime**: Target 99.9%
- **Error Rate**: Target <0.1%
- **Background Worker Success**: Target >99%

---

## Conclusion

You're now ready to use **Enterprise V8**!

**What you have**:
- ✅ 31 PHP files working together
- ✅ 9 database tables with complete schema
- ✅ 13 REST API endpoints
- ✅ 15 specialized admin tabs
- ✅ Intelligent lead scoring & routing
- ✅ Revenue-focused content optimization
- ✅ Automated background workers
- ✅ Real-time analytics & reporting

**Your first 30 days**:
1. Week 1: Setup & testing
2. Week 2: Content production
3. Week 3: Lead management
4. Week 4: Optimization & scaling

**Expected results** (after 90 days):
- 50-100 articles in portfolio
- 80% scored >60 (SCALE/BOOST categories)
- 500+ leads processed
- 20%+ conversion rate
- 10,000+ PLN monthly revenue

---

*Last Updated: 2026-05-03*
*PearBlog Engine - Enterprise V8*
*Version 8.0.0-enterprise*

**Ready to start?** Log in to your WordPress admin and click **PearBlog Engine** to access the Enterprise V8 dashboard!
