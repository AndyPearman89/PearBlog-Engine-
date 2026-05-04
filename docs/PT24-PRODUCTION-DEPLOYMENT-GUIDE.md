# PT24.PRO - Production Deployment Guide

**Date:** 2026-05-04
**Target Launch:** May 10, 2026 at 10:00 AM CEST
**Version:** Enterprise V8 with PT24 Core
**Status:** 🟢 READY FOR DEPLOYMENT

---

## 🎯 Executive Summary

This guide provides complete instructions for deploying PT24.PRO to production with all Enterprise V8 features enabled. All code-level work is complete and tested. This document covers the deployment process, verification procedures, and rollback plans.

**What's Being Deployed:**
- PT24.PRO Homepage V4 (HI-PRO)
- Enterprise Admin Dashboard V8 (15 tabs)
- PT24 AI Lead Engine V2 (intelligent routing)
- Database infrastructure (9 tables)
- SEO optimization with Schema.org
- Security hardening (output escaping, sanitization)

---

## 📋 Pre-Deployment Checklist

### ✅ Completed (Code Ready)
- [x] Homepage V4 template created and tested
- [x] Database schema defined (pt24-database.php)
- [x] SEO meta tags implemented (pt24-seo-meta.php)
- [x] Security hardening (output escaping, nonces, sanitization)
- [x] Deployment scripts created (pt24-quick-fixes.sh, pt24-build-production.sh)
- [x] Enterprise V8 enabled and documented
- [x] All code pushed to branch: claude/pearblog-engine-core-architecture

### ⚠️ Pre-Deployment Tasks (Server Access Required)

**1. Server Preparation**
- [ ] Ensure WordPress 6.0+ is installed
- [ ] PHP 8.1+ verified
- [ ] MySQL 8.0+ or MariaDB 10.5+ verified
- [ ] WP-CLI installed and accessible
- [ ] SSL certificate installed and working
- [ ] Server has at least 2GB RAM, 10GB disk space

**2. Backup Everything**
- [ ] Database backup: `wp db export backup-pre-pt24-$(date +%Y%m%d).sql`
- [ ] Files backup: `tar -czf backup-wordpress-$(date +%Y%m%d).tar.gz /var/www/html`
- [ ] Configuration backup: Copy wp-config.php
- [ ] Store backups off-server (S3, Dropbox, etc.)

**3. Access & Credentials**
- [ ] SSH access to production server
- [ ] WordPress admin credentials
- [ ] Database credentials
- [ ] DNS access (if needed)
- [ ] CDN/Cloudflare access (if used)

**4. Monitoring Setup**
- [ ] Google Analytics 4 tracking code ready
- [ ] Server monitoring enabled (optional: monitoring stack)
- [ ] Error logging configured
- [ ] Uptime monitoring (UptimeRobot, Pingdom, etc.)

---

## 🚀 Deployment Process

### Step 1: Deploy Code to Production

**Option A: Direct Deployment (Recommended if you have SSH access)**

```bash
# SSH into your production server
ssh user@your-production-server.com

# Navigate to WordPress root
cd /var/www/html

# Pull the latest code (adjust to your deployment method)
# If using Git on server:
git fetch origin
git checkout claude/pearblog-engine-core-architecture
git pull origin claude/pearblog-engine-core-architecture

# Or if deploying from local:
# rsync -avz --exclude 'node_modules' \
#   /local/path/to/PearBlog-Engine-/ \
#   user@server:/var/www/html/
```

**Option B: FTP/SFTP Deployment**

Upload these directories/files to your WordPress installation:
```
mu-plugins/pearblog-engine/          → WordPress mu-plugins directory
theme/pearblog-theme/                → WordPress themes directory
scripts/                             → Can be placed in a deploy/ directory
```

**Critical Files to Deploy:**
```
theme/pearblog-theme/
├── page-pt24-home-v4.php           (Homepage V4 template)
├── inc/
│   ├── pt24-database.php           (Database management)
│   ├── pt24-seo-meta.php           (SEO meta tags)
│   ├── pt24-form-handler.php       (Lead form handler)
│   ├── pt24-landing-cpt.php        (Landing pages CPT)
│   └── pt24-cli-commands.php       (WP-CLI commands)
├── assets/
│   ├── css/pt24-home-v4.css        (Homepage styles)
│   └── js/pt24-home-v4.js          (Homepage JavaScript)
└── functions.php                    (Updated with new includes)

mu-plugins/pearblog-engine/
├── pearblog-engine.php             (Main plugin file - Enterprise V8 enabled)
└── src/                            (All Enterprise V8 modules)
```

### Step 2: Run Deployment Script

```bash
# Make script executable (if not already)
chmod +x scripts/pt24-quick-fixes.sh

# Run the deployment script
cd /path/to/wordpress
./scripts/pt24-quick-fixes.sh
```

**What this script does:**
1. ✅ Creates database tables (wp_pt24_leads, wp_pt24_business_stats)
2. ✅ Flushes rewrite rules
3. ✅ Verifies asset files exist
4. ✅ Checks security features (nonces, sanitization)
5. ✅ Generates deployment report

**Expected Output:**
```
================================
PT24.PRO Quick Fixes Deployment
================================

[1/5] Creating database tables...
✓ Database tables created

[2/5] Flushing rewrite rules...
✓ Rewrite rules flushed

[3/5] Checking asset files...
✓ All assets found

[4/5] Verifying security features...
✓ Security features verified

[5/5] Generating deployment report...
✓ Report saved to: docs/deployment-report-20260504.txt

================================
✓ Deployment completed successfully
================================
```

### Step 3: Configure Homepage V4

```bash
# Create the homepage page using WP-CLI
wp post create \
  --post_type=page \
  --post_title="Home" \
  --post_status=publish \
  --page_template="page-pt24-home-v4.php" \
  --post_content="<!-- PT24.PRO Homepage V4 -->"

# Get the page ID (replace XXX with actual ID from above command)
PAGE_ID=XXX

# Set as front page
wp option update show_on_front 'page'
wp option update page_on_front $PAGE_ID

# Verify
wp option get show_on_front
wp option get page_on_front
```

### Step 4: Initialize PT24 Platform

```bash
# Initialize platform data
wp pt24 init

# Generate initial landing pages (optional - for testing)
wp pt24 generate-pages --batch=10

# Verify
wp pt24 stats
```

### Step 5: Verify Enterprise V8 Activation

```bash
# Check Enterprise V8 is enabled
wp option get pearblog_admin_version
# Should return: v8-enterprise

# Or check in code
grep "PEARBLOG_ADMIN_VERSION" wp-content/mu-plugins/pearblog-engine/pearblog-engine.php
# Should show: define( 'PEARBLOG_ADMIN_VERSION', 'v8-enterprise' );
```

### Step 6: Configure AI Services (Optional but Recommended)

```bash
# Set OpenAI API key for AI lead replies
wp option update pearblog_openai_key "sk-your-openai-key"

# Set SMS provider for business notifications
wp option update pearblog_sms_provider "smsapi"
wp option update pearblog_sms_token "your-smsapi-token"

# Configure lead scoring weights (defaults are optimal)
wp option update pt24_lead_urgency_weight 30
wp option update pt24_lead_budget_weight 25
wp option update pt24_lead_clarity_weight 20
wp option update pt24_lead_location_weight 15
wp option update pt24_lead_demand_weight 10

# Configure SLA times (in seconds)
wp option update pt24_sla_premium_plus 1800  # 30 minutes
wp option update pt24_sla_premium 7200       # 2 hours
wp option update pt24_sla_free 0             # No SLA
```

---

## ✅ Post-Deployment Verification

### 1. Homepage Check

**Visit:** https://pt24.pro

**Verify:**
- [ ] Homepage loads without errors
- [ ] Purple gradient background displays correctly
- [ ] All 10 sections visible:
  - [ ] Hero with search bar
  - [ ] Lead form (scrolls to on click)
  - [ ] Services grid (6 categories)
  - [ ] How It Works (3 steps)
  - [ ] Live activity feed
  - [ ] Top rankings
  - [ ] Cost insights
  - [ ] Content hub/guides
  - [ ] Final CTA
  - [ ] SEO footer
- [ ] Search bar works (try "mechanik warszawa")
- [ ] Lead form accepts input
- [ ] All links work
- [ ] Mobile responsive

### 2. Lead Form Testing

**Test Form Submission:**

1. Fill out lead form:
   - Service: Mechanik samochodowy
   - Location: Warszawa
   - Description: Auto nie odpala, potrzebna pilna naprawa
   - Name: Test User
   - Phone: +48 123 456 789

2. Submit form

3. Verify:
   - [ ] Success message appears
   - [ ] No JavaScript errors in console
   - [ ] Database entry created:
     ```bash
     wp db query "SELECT * FROM wp_pt24_leads ORDER BY id DESC LIMIT 1;"
     ```

### 3. Database Verification

```bash
# Check tables exist
wp db query "SHOW TABLES LIKE 'wp_pt24%';"
# Should show: wp_pt24_leads, wp_pt24_business_stats

wp db query "SHOW TABLES LIKE 'wp_leadai%';"
# Should show: wp_leadai_leads, wp_leadai_events, wp_leadai_notifications, wp_leadai_analytics

# Check table structure
wp db query "DESCRIBE wp_pt24_leads;"
wp db query "DESCRIBE wp_pt24_business_stats;"

# Check initial data
wp db query "SELECT COUNT(*) FROM wp_pt24_leads;"
```

### 4. SEO Meta Tags Verification

**View Page Source:** https://pt24.pro

**Verify these tags exist:**
```html
<!-- Basic SEO -->
<meta name="description" content="...">
<link rel="canonical" href="...">

<!-- Open Graph -->
<meta property="og:title" content="...">
<meta property="og:description" content="...">
<meta property="og:url" content="...">

<!-- Twitter Cards -->
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="...">

<!-- Schema.org JSON-LD -->
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "WebSite",
  ...
}
</script>
```

### 5. Enterprise Dashboard Check

**Access:** WordPress Admin → 🚀 PearBlog v8

**Verify:**
- [ ] 15 tabs visible
- [ ] Dashboard Enterprise loads
- [ ] Real-Time Analytics accessible
- [ ] AI Strategy tab loads
- [ ] Leads & CRM tab loads
- [ ] No PHP errors in WordPress debug log

### 6. Performance Testing

```bash
# Test page load speed
curl -o /dev/null -s -w "Time Total: %{time_total}s\n" https://pt24.pro

# Should be < 3 seconds
```

**Run PageSpeed Insights:**
- Visit: https://pagespeed.web.dev/
- Enter: https://pt24.pro
- Target: 80+ score

**Check Core Web Vitals:**
- LCP (Largest Contentful Paint): < 2.5s ✅
- FID (First Input Delay): < 100ms ✅
- CLS (Cumulative Layout Shift): < 0.1 ✅

### 7. Error Log Check

```bash
# Check WordPress debug log
tail -f /path/to/wordpress/wp-content/debug.log

# Check PHP error log
tail -f /var/log/apache2/error.log  # or nginx/error.log

# Should see no critical errors
```

---

## 🔍 Monitoring & Metrics

### Launch Day KPIs (First 24 Hours)

**Traffic Metrics:**
- [ ] Unique visitors > 100
- [ ] Page views > 500
- [ ] Bounce rate < 60%
- [ ] Avg session duration > 2 minutes

**Conversion Metrics:**
- [ ] Lead form submissions > 10
- [ ] Form conversion rate > 3%
- [ ] Service search usage > 50

**Technical Metrics:**
- [ ] Server uptime: 99.9%
- [ ] Average page load < 3s
- [ ] Error rate < 0.1%
- [ ] No critical errors

**AI Metrics (If enabled):**
- [ ] Lead scoring accuracy > 85%
- [ ] Correct service classification > 90%
- [ ] SLA compliance > 80%

### Monitoring Dashboard

**Access Enterprise Dashboard:**
WordPress Admin → 🚀 PearBlog v8 → Dashboard Enterprise

**Watch These Metrics:**
1. **Leads Section:** NEW / WAITING / CLOSED counts
2. **Traffic:** Real-time visitor count
3. **Conversion:** Form submission rate
4. **Performance:** Page load times
5. **Errors:** Any system errors

### External Monitoring

**Google Analytics 4:**
- Real-time dashboard: visitors, pages, events
- Conversion tracking: form submissions
- Traffic sources: organic, direct, referral

**Server Monitoring (if deployed):**
```bash
# If monitoring stack deployed
cd /path/to/monitoring
docker-compose ps
# All services should be "Up"
```

---

## 🚨 Troubleshooting

### Issue: Homepage Shows 404

**Solution:**
```bash
# Flush rewrite rules
wp rewrite flush

# Verify homepage is set
wp option get show_on_front  # Should be: page
wp option get page_on_front  # Should be: [PAGE_ID]

# Check page exists
wp post list --post_type=page --field=ID,post_title
```

### Issue: Lead Form Not Submitting

**Check:**
1. JavaScript console for errors
2. AJAX endpoint:
   ```bash
   curl -X POST https://pt24.pro/wp-admin/admin-ajax.php \
     -d "action=pt24_submit_lead&nonce=xxx&name=Test&phone=123&service=mechanik&location=Warszawa"
   ```
3. Verify handler is registered:
   ```bash
   wp eval "echo has_action('wp_ajax_pt24_submit_lead') ? 'OK' : 'MISSING';"
   wp eval "echo has_action('wp_ajax_nopriv_pt24_submit_lead') ? 'OK' : 'MISSING';"
   ```

### Issue: Database Tables Missing

**Solution:**
```bash
# Manually create tables
wp eval "
require_once('/path/to/wordpress/wp-content/themes/pearblog-theme/inc/pt24-database.php');
pt24_create_database_tables();
"

# Verify
wp db query "SHOW TABLES LIKE 'wp_pt24%';"
```

### Issue: SEO Meta Tags Not Showing

**Check:**
```bash
# Verify module is loaded
wp eval "echo function_exists('pt24_output_seo_meta') ? 'LOADED' : 'MISSING';"

# Check if hooked to wp_head
wp eval "echo has_action('wp_head', 'pt24_output_seo_meta') ? 'HOOKED' : 'NOT HOOKED';"

# View source
curl -s https://pt24.pro | grep -i 'og:title'
```

### Issue: Enterprise Dashboard Not Showing

**Check:**
```bash
# Verify Enterprise V8 is enabled
wp eval "echo defined('PEARBLOG_ADMIN_VERSION') ? PEARBLOG_ADMIN_VERSION : 'NOT DEFINED';"
# Should return: v8-enterprise

# Check plugin is active
wp plugin list | grep pearblog-engine

# Clear cache
wp cache flush
```

### Issue: Slow Performance

**Quick Fixes:**
```bash
# Enable object caching (if Redis available)
wp config set WP_CACHE true

# Optimize database
wp db optimize

# Regenerate thumbnails
wp media regenerate --yes

# Clear all caches
wp cache flush
```

---

## 🔄 Rollback Procedure

**If Critical Issues Occur:**

### Step 1: Immediate Mitigation

```bash
# Put site in maintenance mode
wp maintenance-mode activate

# Or create .maintenance file
echo '<?php $upgrading = time(); ?>' > /var/www/html/.maintenance
```

### Step 2: Restore Previous Version

**Option A: Revert Code**
```bash
# If using Git
git checkout [previous-stable-commit]
git push -f origin main  # Only if absolutely necessary

# Flush cache
wp cache flush
wp rewrite flush
```

**Option B: Restore from Backup**
```bash
# Restore database
wp db import backup-pre-pt24-20260509.sql

# Restore files
tar -xzf backup-wordpress-20260509.tar.gz -C /var/www/html

# Clear cache
wp cache flush
```

### Step 3: Verify Rollback

```bash
# Check site loads
curl -I https://pt24.pro

# Remove maintenance mode
wp maintenance-mode deactivate
# Or: rm /var/www/html/.maintenance
```

### Step 4: Post-Mortem

- Document what went wrong
- Identify root cause
- Plan fix
- Schedule re-deployment

---

## 📞 Support Contacts

**Technical Issues:**
- Developer: [Your contact]
- DevOps: [Your contact]
- WordPress Admin: [Your contact]

**Emergency Contacts:**
- On-call Engineer: [Phone]
- Product Owner: [Phone]
- Hosting Support: [Support URL]

**Useful Resources:**
- PT24 Documentation: `/docs/`
- Enterprise V8 Guide: `/docs/PT24-ENTERPRISE-V8-INTEGRATION.md`
- Launch Day Plan: `/docs/LAUNCH-DAY-PLAN.md`
- Incident Response: `/docs/INCIDENT-RESPONSE.md`

---

## ✅ Post-Launch Tasks (Week 1)

### Days 1-3 (May 10-12)
- [ ] Monitor metrics daily
- [ ] Respond to user feedback
- [ ] Fix any critical bugs
- [ ] Optimize performance based on real data
- [ ] Review error logs

### Days 4-7 (May 13-16)
- [ ] Analyze conversion rates
- [ ] A/B test optimizations
- [ ] SEO monitoring (Google Search Console)
- [ ] Content updates based on analytics
- [ ] Plan feature improvements

### Week 2+
- [ ] Launch retrospective meeting
- [ ] Document lessons learned
- [ ] Plan v8.1 improvements
- [ ] Scale infrastructure if needed
- [ ] Marketing campaigns

---

## 🎯 Success Criteria

**Launch is successful if:**
- ✅ Site is accessible and fast (< 3s load time)
- ✅ No critical errors in 24 hours
- ✅ 99.9% uptime in first week
- ✅ Lead form working and capturing data
- ✅ At least 10 quality leads in first week
- ✅ Positive user feedback
- ✅ Core Web Vitals in "Good" range
- ✅ Enterprise Dashboard accessible

**Metrics to Beat (by end of Week 1):**
- 1,000+ unique visitors
- 50+ lead submissions
- 5%+ conversion rate
- 80+ PageSpeed score
- 0 critical security issues

---

## 📝 Deployment Checklist Summary

### Pre-Deployment ✅
- [x] Code complete and tested
- [x] Documentation created
- [x] Deployment scripts ready
- [ ] Backups created
- [ ] Server access verified
- [ ] Monitoring configured

### Deployment 🚀
- [ ] Code deployed to server
- [ ] Deployment script executed
- [ ] Homepage configured
- [ ] Database verified
- [ ] Enterprise V8 verified
- [ ] AI services configured (optional)

### Verification ✅
- [ ] Homepage loads correctly
- [ ] Lead form works
- [ ] SEO meta tags present
- [ ] Enterprise dashboard accessible
- [ ] Performance acceptable
- [ ] No critical errors

### Launch 🎉
- [ ] Maintenance mode disabled
- [ ] Public announcement
- [ ] Team monitoring
- [ ] Support ready
- [ ] Celebrating! 🎊

---

**Document Version:** 1.0.0
**Last Updated:** 2026-05-04
**Status:** Ready for Production Deployment
**Launch Target:** May 10, 2026 at 10:00 AM CEST

**🚀 PT24.PRO IS READY TO LAUNCH!**
