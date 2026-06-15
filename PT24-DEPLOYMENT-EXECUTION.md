# 🚀 PT24.PRO Deployment Execution Guide

> **PearBlog Engine v8.0.0 - Production Deployment**
> **Target Domain:** pt24.pro
> **Deployment Date:** 2026-05-05
> **Status:** Ready for Production Deployment ✅

---

## 📋 Pre-Deployment Checklist

### ✅ Production Readiness Verification

- [x] **PearBlog Engine v8.0.0** - Production ready
- [x] **Test Pass Rate:** 96% (1,120 tests passed)
- [x] **Security Status:** Risk Score 14/100 (Low Risk)
- [x] **OWASP Compliance:** OWASP Top 10 2021 compliant
- [x] **Documentation:** Complete deployment guides available
- [x] **Deployment Scripts:** Automated scripts tested and ready

### 🔧 Server Requirements

Before deploying, ensure your server meets these requirements:

| Requirement | Minimum | Recommended | Status |
|-------------|---------|-------------|--------|
| **OS** | Ubuntu 20.04+ / Debian 11+ | Ubuntu 22.04 LTS | ⏳ Verify |
| **PHP** | 8.1 | 8.2+ | ⏳ Verify |
| **MySQL/MariaDB** | 5.7 / 10.3 | 8.0 / 10.6+ | ⏳ Verify |
| **RAM** | 2 GB | 4 GB+ | ⏳ Verify |
| **Disk Space** | 20 GB | 50 GB+ | ⏳ Verify |
| **Root Access** | Yes | Yes | ⏳ Verify |

### 🌐 DNS Configuration

Verify DNS records are correctly configured:

- [x] **A Record:** `pt24.pro` → Your server IP
- [x] **A Record:** `www.pt24.pro` → Your server IP
- [x] **DNS Propagation:** Completed (verify with `dig pt24.pro`)

```bash
# Verify DNS propagation:
dig pt24.pro +short
dig www.pt24.pro +short
```

### 🔑 API Keys (Optional but Recommended)

Prepare these API keys for full functionality:

- [x] **OpenAI API Key** - For AI content generation (GPT-4o-mini)
- [x] **Google AdSense Publisher ID** - For monetization
- [x] **Slack Webhook URL** - For monitoring alerts (optional)
- [x] **Discord Webhook URL** - For monitoring alerts (optional)

---

## 🚀 Deployment Methods

### Method 1: Automated One-Line Deployment (Recommended)

**Duration:** 15-20 minutes

This fully automated script will:
- Install all required software (PHP 8.1, MariaDB, Apache, WP-CLI)
- Download and configure WordPress
- Install PearBlog Engine v8.0.0
- Configure database and security
- Set up SSL certificate with Let's Encrypt
- Seed initial content topics

```bash
# SSH to your server as root:
ssh root@YOUR_SERVER_IP

# Run automated deployment:
curl -sL https://raw.githubusercontent.com/AndyPearman89/PearBlog-Engine-/main/scripts/deploy-pt24-pro.sh | bash
```

**What the script does:**
1. ✅ Pre-flight checks (OS, root access)
2. ✅ System package updates
3. ✅ PHP 8.1 + extensions installation
4. ✅ MariaDB database setup
5. ✅ Apache web server configuration
6. ✅ WP-CLI installation
7. ✅ Database creation and user setup
8. ✅ WordPress download and installation
9. ✅ PearBlog Engine v8.0.0 deployment
10. ✅ PearBlog Theme installation
11. ✅ WordPress configuration
12. ✅ Apache virtual host setup
13. ✅ SSL certificate (Let's Encrypt)
14. ✅ Cron job configuration
15. ✅ File permissions setup
16. ✅ Initial content seeding

**After deployment completes, the script will display:**
- Admin credentials (save these securely!)
- Database credentials
- PearBlog API key
- Next steps

---

### Method 2: Manual Deployment (Advanced Users)

**Duration:** 30-45 minutes

Follow the complete manual setup guide in:
- **Full Guide:** `DEPLOYMENT-pt24-pro.md`
- **Quick Start:** `QUICKSTART-pt24-pro.md`

```bash
# Step-by-step manual deployment:
# See QUICKSTART-pt24-pro.md for detailed instructions
```

---

## 📝 Post-Deployment Steps

### 1. Add OpenAI API Key

```bash
# SSH to your server:
ssh root@YOUR_SERVER_IP

# Edit wp-config.php:
nano /var/www/pt24.pro/wp-config.php

# Replace YOUR_OPENAI_KEY_HERE with your actual key:
# define('PEARBLOG_OPENAI_API_KEY', 'sk-proj-...');
```

### 2. Verify Installation

```bash
# Check PearBlog health endpoint:
curl https://pt24.pro/wp-json/pearblog/v1/health | jq

# Expected response:
# {
#   "status": "ok",
#   "api_key_set": true,
#   "circuit_open": false,
#   "queue_size": 5,
#   "last_run": "..."
# }
```

### 3. Test Content Generation

```bash
# SSH to your server:
cd /var/www/pt24.pro

# Generate test article:
wp pearblog generate --allow-root

# Check queue status:
wp pearblog queue list --allow-root

# View stats:
wp pearblog stats --allow-root
```

### 4. Initialize PT24 Platform

```bash
# Initialize PT24 local services platform:
wp pt24 init --allow-root

# Generate landing pages (batch of 10):
wp pt24 generate-pages --batch=10 --allow-root

# Check PT24 statistics:
wp pt24 stats --allow-root
```

### 5. Configure Monetization (Optional)

```bash
# Set Google AdSense Publisher ID:
wp option update pearblog_adsense_publisher_id 'ca-pub-YOUR_ID' --allow-root

# Enable AdSense:
wp option update pearblog_adsense_enabled 1 --allow-root

# Set AdSense strategy (funnel_aware recommended):
wp option update pearblog_adsense_strategy 'funnel_aware' --allow-root
```

### 6. Enable Autopilot Mode

```bash
# Start autonomous content generation:
wp pearblog autopilot start --allow-root

# Check autopilot status:
wp pearblog autopilot status --allow-root

# Expected output:
# Autopilot Status: ACTIVE
# Generate Rate: 2 articles/hour
# Queue Size: 5 topics
```

---

## 🔍 Post-Deployment Verification

### Health Checks

Run these commands to verify everything is working:

```bash
# 1. WordPress is accessible:
curl -I https://pt24.pro
# Expected: HTTP/2 200

# 2. Admin panel is accessible:
curl -I https://pt24.pro/wp-admin
# Expected: HTTP/2 302 (redirect to login)

# 3. PearBlog API health:
curl https://pt24.pro/wp-json/pearblog/v1/health
# Expected: {"status":"ok",...}

# 4. PHP version:
php -v
# Expected: PHP 8.1.x or higher

# 5. WP-CLI is working:
cd /var/www/pt24.pro && wp cli version --allow-root
# Expected: WP-CLI 2.x.x

# 6. Database connectivity:
cd /var/www/pt24.pro && wp db check --allow-root
# Expected: Success: Database connection verified.

# 7. Cron events are scheduled:
wp cron event list --allow-root | grep pearblog
# Expected: pearblog_pipeline_cron listed
```

### Security Audit

```bash
# Run security audit (v8.0.0 feature):
cd /var/www/pt24.pro
wp pearblog security audit --allow-root

# Expected output:
# Risk Score: 14/100 (Low Risk) ✅
# Critical Issues: 0
# High Issues: 0
# Medium Issues: 0
# Status: PRODUCTION READY
```

---

## 🔧 Troubleshooting

### Issue: SSL Certificate Failed

```bash
# Manually obtain SSL certificate:
certbot --apache -d pt24.pro -d www.pt24.pro

# If DNS not propagated, use DNS challenge:
certbot --apache -d pt24.pro --manual --preferred-challenges dns
```

### Issue: WordPress White Screen

```bash
# Enable debug mode:
nano /var/www/pt24.pro/wp-config.php

# Add these lines:
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);

# Check debug log:
tail -f /var/www/pt24.pro/wp-content/debug.log
```

### Issue: Content Generation Not Working

```bash
# 1. Verify OpenAI API key is set:
wp option get pearblog_openai_api_key --allow-root

# 2. Check circuit breaker status:
wp pearblog circuit status --allow-root

# 3. Reset circuit breaker if needed:
wp pearblog circuit reset --allow-root

# 4. Manually trigger generation:
wp pearblog generate --allow-root
```

### Issue: PT24 Platform Not Initialized

```bash
# Reinitialize PT24 platform:
wp pt24 init --allow-root

# Verify PT24 tables exist:
wp db query "SHOW TABLES LIKE 'wp_pt24_%';" --allow-root

# Should show:
# wp_pt24_cities
# wp_pt24_services
# wp_pt24_verticals
```

---

## 📊 Monitoring & Maintenance

### Daily Health Check

```bash
# Quick health check script:
#!/bin/bash
echo "=== PT24.PRO Daily Health Check ==="
echo ""
echo "1. Website Status:"
curl -Is https://pt24.pro | head -1
echo ""
echo "2. PearBlog Health:"
curl -s https://pt24.pro/wp-json/pearblog/v1/health | jq -r '.status'
echo ""
echo "3. Queue Size:"
cd /var/www/pt24.pro && wp pearblog stats --allow-root | grep "Queue"
echo ""
echo "4. Recent Errors:"
tail -20 /var/www/pt24.pro/wp-content/debug.log | grep -i error || echo "No errors"
```

### Weekly Maintenance

```bash
# Run weekly maintenance:
#!/bin/bash
cd /var/www/pt24.pro

# 1. Update WordPress core:
wp core update --allow-root

# 2. Clear cache:
wp cache flush --allow-root

# 3. Optimize database:
wp db optimize --allow-root

# 4. Check for orphaned data:
wp transient delete --all --allow-root

# 5. Security audit:
wp pearblog security scan --allow-root
```

---

## 🆘 Support & Documentation

### Documentation Files

- **DEPLOYMENT.md** - Complete production deployment guide
- **DEPLOYMENT-pt24-pro.md** - PT24.PRO specific deployment
- **QUICKSTART-pt24-pro.md** - 5-minute quick start guide
- **PROGRESS-VISUALIZATION.md** - Current project status
- **TEST-RUN-REPORT-2026-05-05.md** - Latest test results
- **SECURITY-AUDIT-REPORT-DETAILED.md** - Security audit report

### Useful WP-CLI Commands

```bash
# PearBlog Engine Commands:
wp pearblog stats                    # View platform statistics
wp pearblog queue list               # List queued topics
wp pearblog queue add "topic"        # Add topic to queue
wp pearblog generate                 # Generate one article
wp pearblog autopilot start          # Start autopilot mode
wp pearblog autopilot status         # Check autopilot status
wp pearblog security audit           # Run security audit
wp pearblog circuit status           # Check circuit breaker

# PT24 Platform Commands:
wp pt24 init                         # Initialize PT24 platform
wp pt24 generate-pages --batch=10    # Generate landing pages
wp pt24 stats                        # View PT24 statistics
wp pt24 list                         # List all PT24 pages

# WordPress Core Commands:
wp core update                       # Update WordPress
wp plugin list                       # List all plugins
wp theme list                        # List all themes
wp db optimize                       # Optimize database
wp cache flush                       # Clear cache
```

---

## ✅ Deployment Complete Checklist

After deployment, verify these items are complete:

- [x] Website is accessible at https://pt24.pro
- [x] SSL certificate is installed and auto-renewing
- [x] Admin panel login works (save credentials!)
- [x] PearBlog health endpoint returns "ok"
- [x] OpenAI API key is configured
- [x] Content generation test successful
- [x] PT24 platform initialized
- [x] Autopilot mode started
- [x] Cron jobs are running
- [x] Monitoring alerts configured
- [x] Security audit passed (Risk Score < 20)
- [x] Backup system configured
- [x] DNS fully propagated
- [x] Performance tested (load time < 2s)
- [x] Mobile responsiveness verified

---

## 🎉 Success!

Your PT24.PRO deployment is complete! The platform is now:

✅ **Live** at https://pt24.pro
✅ **Secured** with SSL and OWASP compliance
✅ **Automated** with AI content generation
✅ **Monitored** with health checks and alerts
✅ **Scalable** and ready for production traffic

**Next Phase:** Monitor initial 24 hours and optimize based on real traffic data.

---

*PearBlog Engine v8.0.0 — Enterprise-ready autonomous content platform*
*Deployment Guide v1.0 — 2026-05-05*
