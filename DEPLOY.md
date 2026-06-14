# 🚀 PT24.PRO - Quick Deployment Guide

**Target Launch:** May 10, 2026 at 10:00 AM CEST
**Version:** Enterprise V8
**Status:** ✅ READY FOR DEPLOYMENT

---

## Quick Start (5 Minutes)

### Option 1: Automated Deployment (Recommended)

```bash
# SSH into your production server
ssh user@pt24.pro

# Navigate to WordPress root
cd /var/www/html

# Run the production deployment script
./scripts/deploy-pt24-production.sh
```

The script will:
1. ✅ Validate prerequisites (WP-CLI, WordPress, PHP 8.1+)
2. ✅ Create automatic backups (database + wp-config.php)
3. ✅ Set up PT24 database tables
4. ✅ Configure WordPress homepage
5. ✅ Optimize and minify assets
6. ✅ Verify security features
7. ✅ Generate deployment report

### Option 2: Manual Deployment

```bash
# 1. Create database tables
wp eval-file theme/pearblog-theme/inc/pt24-database.php --allow-root
wp eval "pt24_create_database_tables();" --allow-root

# 2. Create homepage
PAGE_ID=$(wp post create \
  --post_type=page \
  --post_status=publish \
  --post_title="PT24 Home V4" \
  --page_template="page-pt24-home-v4.php" \
  --porcelain \
  --allow-root)

# 3. Set as front page
wp option update show_on_front page --allow-root
wp option update page_on_front $PAGE_ID --allow-root

# 4. Flush rewrite rules
wp rewrite flush --allow-root

# 5. Run quick fixes
./scripts/pt24-quick-fixes.sh
```

---

## Post-Deployment Verification (2 Minutes)

### 1. Test Homepage

```bash
# Visit homepage
curl -I https://pt24.pro
# Should return: HTTP/2 200
```

Visit in browser: **https://pt24.pro**

**Verify:**
- ✅ Purple gradient background
- ✅ All 10 sections visible
- ✅ Lead form functional
- ✅ Mobile responsive

### 2. Test Lead Form

**Submit test lead:**
- Service: "Mechanik samochodowy"
- City: "Warszawa"
- Description: "Test lead submission"
- Name: "Test User"
- Phone: "+48 123 456 789"

**Verify in database:**
```bash
wp db query "SELECT * FROM wp_pt24_leads ORDER BY id DESC LIMIT 1;" --allow-root
```

### 3. Verify Database

```bash
# Check PT24 tables exist
wp db query "SHOW TABLES LIKE 'wp_pt24_%';" --allow-root
# Should show: wp_pt24_leads, wp_pt24_business_stats

# Check lead count
wp db query "SELECT COUNT(*) FROM wp_pt24_leads;" --allow-root
```

### 4. Check Enterprise V8

```bash
# Verify Enterprise V8 is active
grep "PEARBLOG_ADMIN_VERSION" wp-content/mu-plugins/pearblog-engine/pearblog-engine.php
# Should show: define( 'PEARBLOG_ADMIN_VERSION', 'v8-enterprise' );
```

---

## What Gets Deployed

### Core Files
```
mu-plugins/pearblog-engine/          → Enterprise V8 (31 files, 9,500+ lines)
  └── src/                           → 15 admin tabs, AI systems

theme/pearblog-theme/                → PT24 Theme
  ├── page-pt24-home-v4.php          → Homepage V4 template
  ├── inc/pt24-database.php          → Database setup
  ├── inc/pt24-seo-meta.php          → SEO optimization
  ├── inc/pt24-form-handler.php      → Lead form handler
  └── assets/
      ├── css/pt24-home-v4.css       → Homepage styles
      └── js/pt24-home-v4.js         → Homepage interactions

scripts/                             → Deployment automation
  ├── deploy-pt24-production.sh      → Master deployment script
  ├── pt24-quick-fixes.sh            → Quick fixes
  └── pt24-build-production.sh       → Asset optimization
```

### Database Tables (9 total)

**PT24 Core:**
- `wp_pt24_leads` - Lead capture & storage
- `wp_pt24_business_stats` - Profile analytics

**Enterprise AI (7 tables):**
- `wp_leadai_leads` - AI-enhanced leads
- `wp_leadai_events` - Lead lifecycle
- `wp_leadai_notifications` - SMS/Email queue
- `wp_leadai_analytics` - Performance metrics
- `wp_poradnik_articles` - Content tracking
- `wp_poradnik_stats` - Content analytics
- `wp_poradnik_events` - Content events

---

## Launch Day Checklist (May 10, 2026)

### Pre-Launch (9:00 AM - 9:55 AM)

- [x] **9:00 AM** - Final database backup
  ```bash
  wp db export backup-launch-day-$(date +%Y%m%d).sql --allow-root
  ```

- [x] **9:15 AM** - Clear all caches
  ```bash
  wp cache flush --allow-root
  wp rewrite flush --allow-root
  # Clear CDN cache (Cloudflare/other)
  ```

- [x] **9:30 AM** - Test homepage load time
  - Target: < 2.5 seconds LCP
  - Tool: Google PageSpeed Insights

- [x] **9:40 AM** - Test lead form
  - Submit test lead
  - Verify database entry
  - Check no console errors

- [x] **9:50 AM** - Final verification
  - All 10 sections visible
  - Mobile responsive working
  - SEO meta tags present

### Launch (10:00 AM)

- [x] **10:00 AM** - Official launch
  - Monitor server load
  - Watch error logs
  - Track first submissions

### Post-Launch (10:00 AM - 11:00 AM)

- [x] **10:15 AM** - Monitor first 15 minutes
  - Check error logs: `tail -f /var/log/apache2/error.log`
  - Monitor lead submissions
  - Verify analytics tracking

- [x] **10:30 AM** - Performance check
  - Run PageSpeed Insights
  - Check Core Web Vitals
  - Monitor server resources

- [x] **11:00 AM** - First hour review
  - Total visitors
  - Lead submissions
  - Error rate
  - Page load times

---

## Emergency Procedures

### Rollback (If Critical Issue)

```bash
# 1. Restore database
wp db import backups/pre-deployment-YYYYMMDD-HHMMSS/database.sql --allow-root

# 2. Restore wp-config.php
cp backups/pre-deployment-YYYYMMDD-HHMMSS/wp-config.php wp-config.php

# 3. Clear caches
wp cache flush --allow-root
wp rewrite flush --allow-root
```

### Quick Fixes

**Homepage not loading:**
```bash
wp rewrite flush --allow-root
wp cache flush --allow-root
```

**Lead form not working:**
```bash
# Check database tables
wp db query "SHOW TABLES LIKE 'wp_pt24_%';" --allow-root

# Recreate tables if needed
wp eval "pt24_create_database_tables();" --allow-root
```

**Performance issues:**
```bash
# Enable object caching
wp plugin install redis-cache --activate --allow-root

# Optimize database
wp db optimize --allow-root
```

---

## Monitoring & Analytics

### Set Up Monitoring (Optional but Recommended)

```bash
# Deploy monitoring stack
cd monitoring/
docker-compose up -d

# Access dashboards:
# - Grafana: http://your-server:3000
# - Prometheus: http://your-server:9090
```

### Configure Google Analytics

```bash
# Add GA4 tracking code to wp-config.php or use plugin
wp plugin install google-site-kit --activate --allow-root
```

### Error Monitoring

```bash
# Watch error logs in real-time
tail -f /var/log/apache2/error.log

# Or use WP-CLI
wp plugin install query-monitor --activate --allow-root
```

---

## Success Metrics (First 24 Hours)

### Technical KPIs
- ✅ **Uptime:** > 99.9%
- ✅ **Page Load Time:** < 2.5s
- ✅ **Error Rate:** < 0.1%
- ✅ **Mobile Score:** > 90/100

### Business KPIs
- 🎯 **Unique Visitors:** Target varies
- 🎯 **Lead Submissions:** Target varies
- 🎯 **Conversion Rate:** 2-5% (industry standard)
- 🎯 **Bounce Rate:** < 60%

---

## Support & Documentation

### Complete Documentation

1. **PT24-PRODUCTION-DEPLOYMENT-GUIDE.md** (689 lines)
   - Complete deployment instructions
   - Troubleshooting guide
   - Monitoring setup

2. **PT24-ENTERPRISE-V8-INTEGRATION.md** (613 lines)
   - Enterprise features overview
   - Admin dashboard (15 tabs)
   - AI Lead Engine documentation

3. **This File (DEPLOY.md)**
   - Quick reference guide
   - Launch day procedures

### Key Commands Reference

```bash
# PT24 WP-CLI Commands
wp pt24 init                    # Initialize platform
wp pt24 generate-pages          # Generate landing pages
wp pt24 stats                   # View statistics
wp pt24 list                    # List PT24 pages
wp pt24 flush-rewrites          # Flush rewrite rules

# Database Management
wp db export backup.sql         # Backup database
wp db import backup.sql         # Restore database
wp db query "SQL"               # Run SQL query

# Cache Management
wp cache flush                  # Clear object cache
wp rewrite flush                # Flush rewrite rules
wp transient delete --all       # Clear transients
```

---

## Contact & Issues

- **Documentation:** `/docs/` directory
- **Logs:** `/docs/PRODUCTION-DEPLOYMENT-*.log`
- **Backups:** `/backups/pre-deployment-*/`

---

## 🎯 Ready to Deploy?

```bash
# Run this command to deploy PT24.PRO to production:
./scripts/deploy-pt24-production.sh
```

**All preparation complete. Platform ready for May 10, 2026 launch! 🚀**
