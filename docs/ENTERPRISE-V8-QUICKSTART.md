# Enterprise v8 Admin — Quick Start Guide

Get started with PearBlog Enterprise v8 Admin in **5 minutes**.

## Prerequisites

- WordPress 5.8+
- PHP 7.4+
- PearBlog Engine 8.0.0+ installed
- Administrator access

## Step 1: Verify Installation

Check that Enterprise v8 is enabled:

```bash
# Check plugin version
wp plugin list | grep pearblog-engine

# Verify admin version constant
grep "PEARBLOG_ADMIN_VERSION" mu-plugins/pearblog-engine/pearblog-engine.php
```

You should see:
```php
define( 'PEARBLOG_ADMIN_VERSION', 'v8-enterprise' );
```

## Step 2: Access the Admin

Navigate to:
```
https://your-site.com/wp-admin/admin.php?page=pearblog-enterprise-v8
```

Or click **🚀 PearBlog v8** in the WordPress admin menu (top position).

## Step 3: Initial Configuration

### 3.1 Configure AI Provider

Go to **Settings Enterprise** tab:

```bash
wp option update pearblog_ai_provider "openai"
wp option update pearblog_ai_api_key "sk-..."
wp option update pearblog_ai_model "gpt-4"
```

Or use the UI to configure your AI provider (OpenAI, Anthropic, or Cohere).

### 3.2 Set Your Industry

```bash
wp option update pearblog_industry "poradnik"  # or your industry
```

Available industries: `poradnik`, `guide`, `remont`, `budowa`, `pt24`, `local-services`

### 3.3 Configure AdSense (Optional)

Go to **Revenue Center** tab or use CLI:

```bash
wp option update pearblog_adsense_publisher_id "ca-pub-XXXXXXXXX"
wp option update pearblog_adsense_enabled 1
wp option update pearblog_adsense_strategy "funnel_aware"
```

## Step 4: Generate Your First Content

### Option A: Via Dashboard

1. Go to **AI Strategy** tab
2. Set keyword source to "Automatic"
3. Enable auto discovery
4. Set daily limit to 10 keywords
5. Save settings

6. Go to **Content Engine** tab
7. Set batch count to 5
8. Check "Publish immediately"
9. Click "Start Batch Generation"

### Option B: Via CLI

```bash
# Add topics to queue
wp pearblog queue add "how to fix leaky faucet"
wp pearblog queue add "best plumber in warsaw"
wp pearblog queue add "cost of bathroom renovation"

# Generate content
wp pearblog generate --count=3
```

## Step 5: Monitor Performance

### Real-Time Dashboard

Go to **Real-Time Analytics** tab to see:
- Live visitors
- Revenue/hour
- Conversions
- Error rate

Updates every 5 seconds automatically.

### Performance Metrics

Go to **Performance** tab to check:
- Database query performance
- Cache hit ratio
- Memory usage
- API response times

## Common Tasks

### Enable Dark Mode

Click the 🌙 icon in the top bar. Theme preference is saved automatically.

### Switch Language

Click 🇬🇧 (or 🇵🇱) in the top bar to toggle between English and Polish.

### Export Audit Logs

1. Go to **Security & Audit** tab
2. Click "📥 Export" button
3. Download CSV file

### Generate Reports

1. Go to **Advanced Reports** tab
2. Click "Generate" for desired report type:
   - Revenue Report
   - Content Performance
   - SEO Report
   - AI Cost Analysis
3. Choose export format (CSV, PDF, JSON, Excel)
4. Download report

## Automation Setup

### Enable Auto Content Generation

1. **AI Strategy** tab:
   - Set keyword source to "Hybrid"
   - Enable auto discovery
   - Set daily limit to 20

2. **Automation Pro** tab:
   - Enable content pipeline cron
   - Set frequency to daily
   - Configure batch size to 10

3. **Content Engine** tab:
   - Enable auto-publishing
   - Enable image generation (if using DALL-E)

### Verify Cron Jobs

```bash
# List all PearBlog cron events
wp cron event list | grep pearblog

# Should see:
# - pearblog_pipeline_cron
# - pearblog_topic_research_refresh
# - pearblog_publish_schedule_refresh
```

## Integration Setup

### Google Analytics 4

1. Go to **Integrations** tab
2. Click "Connect" under Google Analytics
3. Enter GA4 Property ID
4. Test connection

### Google AdSense

1. Go to **Revenue Center** tab
2. Enter Publisher ID
3. Choose strategy
4. Enable funnel-aware ads

### Google Search Console

1. Go to **Integrations** tab
2. Click "Connect" under Search Console
3. Verify site ownership
4. Authorize API access

## Troubleshooting

### Admin Page Not Loading

Check that Enterprise v8 is enabled:
```bash
wp option get pearblog_admin_version
# Should return: v8-enterprise
```

If not set:
```bash
wp option update pearblog_admin_version "v8-enterprise"
```

Or add to `pearblog-engine.php`:
```php
define( 'PEARBLOG_ADMIN_VERSION', 'v8-enterprise' );
```

### Tabs Show "Coming Soon"

This should not happen in v8.0.0+. Verify installation:
```bash
# Check file exists
ls -la mu-plugins/pearblog-engine/src/Admin/ContentEngineTab.php
ls -la mu-plugins/pearblog-engine/src/Admin/StrategyTab.php

# Check syntax
php -l mu-plugins/pearblog-engine/src/Admin/AdminPageV8Enterprise.php
```

### Real-Time Updates Not Working

Check settings:
```bash
wp option get pearblog_v8_realtime_enabled
# Should return: 1
```

Enable if disabled:
```bash
wp option update pearblog_v8_realtime_enabled 1
```

### Dark Mode Not Persisting

Check theme option:
```bash
wp option get pearblog_v8_theme
# Should return: light or dark
```

Manually set:
```bash
wp option update pearblog_v8_theme "dark"
```

## Performance Tips

### Optimize for Large Sites

1. **Enable Object Caching**:
   ```bash
   # Install Redis Object Cache plugin
   wp plugin install redis-cache --activate
   wp redis enable
   ```

2. **Database Optimization**:
   ```bash
   # Add indexes for better performance
   wp db query "ALTER TABLE wp_posts ADD INDEX idx_post_type_status (post_type, post_status);"
   ```

3. **Limit Real-Time Polling**:
   ```bash
   # Increase polling interval to 10 seconds (in admin-v8-enterprise.js)
   # Or disable real-time updates:
   wp option update pearblog_v8_realtime_enabled 0
   ```

### Reduce Memory Usage

Configure memory limits in `wp-config.php`:
```php
define( 'WP_MEMORY_LIMIT', '256M' );
define( 'WP_MAX_MEMORY_LIMIT', '512M' );
```

## Next Steps

1. **Read Full Documentation**: See `ENTERPRISE-V8-ADMIN-GUIDE.md` for comprehensive reference
2. **Configure All Tabs**: Explore each tab and configure settings for your use case
3. **Set Up Automation**: Enable auto content generation and scheduling
4. **Monitor Performance**: Use Performance tab to track system health
5. **Review Security**: Check Security & Audit tab regularly

## Support & Resources

- **Documentation**: `/docs/ENTERPRISE-V8-ADMIN-GUIDE.md`
- **Changelog**: `/CHANGELOG.md` (see v8.0.0 section)
- **Issues**: https://github.com/AndyPearman89/PearBlog-Engine-/issues
- **Version**: 8.0.0
- **Access URL**: `/wp-admin/admin.php?page=pearblog-enterprise-v8`

## Quick Reference

### Essential WP-CLI Commands

```bash
# Check system status
wp pearblog status

# Generate content
wp pearblog generate --count=5

# Topic management
wp pearblog queue add "your topic"
wp pearblog queue list
wp pearblog topics research --auto-queue

# SEO V3
wp pearblog seo-v3 stats
wp pearblog seo-v3 keywords --vertical=home-services --intent=commercial

# Export data
wp pearblog export articles --format=csv --limit=100
```

### Essential Options

```bash
# Core settings
wp option update pearblog_ai_provider "openai"
wp option update pearblog_industry "poradnik"
wp option update pearblog_homepage_version "v7"

# Admin settings
wp option update pearblog_v8_theme "dark"
wp option update pearblog_v8_language "pl"
wp option update pearblog_v8_realtime_enabled 1

# Content settings
wp option update pearblog_keyword_source "hybrid"
wp option update pearblog_intent_priority "balanced"

# Monetization
wp option update pearblog_adsense_publisher_id "ca-pub-XXXXX"
wp option update pearblog_adsense_strategy "funnel_aware"
```

## Complete Setup Checklist

- [ ] Verify PearBlog Engine 8.0.0+ installed
- [ ] Access Enterprise v8 admin interface
- [ ] Configure AI provider (OpenAI/Anthropic/Cohere)
- [ ] Set industry vertical
- [ ] Configure keyword discovery strategy
- [ ] Set up AdSense (if monetizing)
- [ ] Connect Google Analytics 4
- [ ] Enable automation workflows
- [ ] Generate first batch of content
- [ ] Review performance dashboard
- [ ] Enable dark mode (optional)
- [ ] Set language preference (EN/PL)
- [ ] Export first report to verify functionality

---

**🎉 You're ready to use Enterprise v8 Admin!**

For detailed information about each tab and feature, see the full guide:
`/docs/ENTERPRISE-V8-ADMIN-GUIDE.md`

🤖 Generated with [Claude Code](https://claude.com/claude-code)
