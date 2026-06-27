# PT24.PRO ENTERPRISE DEPLOYMENT — QUICK REFERENCE

## 🚀 ONE-LINER DEPLOYMENT

```bash
# Direct deployment to pt24.pro
ssh root@pt24.pro 'bash -s' < scripts/deploy-pt24-pro-enterprise.sh
```

---

## 📋 STEP-BY-STEP DEPLOYMENT

### 1. Connect to Server
```bash
ssh root@pt24.pro
```

### 2. Download Deployment Script
```bash
cd /tmp
wget https://raw.githubusercontent.com/AndyPearman89/PearBlog-Engine-/andypearman89-curly-fishstick/scripts/deploy-pt24-pro-enterprise.sh
chmod +x deploy-pt24-pro-enterprise.sh
```

### 3. Run Deployment
```bash
./deploy-pt24-pro-enterprise.sh
```

### 4. Expected Output
```
[PT24] Starting PT24 Enterprise Deployment...
[PT24] Checking requirements...
✓ PHP version: 8.1.0 (or higher)
✓ MySQL/MariaDB available
✓ WP-CLI available
✓ WordPress installation found
[PT24] Deploying PearBlog Engine v9...
✓ PearBlog Engine activated
✓ Enterprise V8 admin dashboard active
[PT24] Deploying PT24 Enterprise Configuration...
✓ PT24 Enterprise Config deployed
✓ PT24 Integration Manager deployed
[PT24] Setting up PT24 database tables...
✓ Database tables created
[PT24] Configuring LeadAI System...
✓ LeadAI configured
[PT24] Configuring Content Linking System...
✓ Content Linking configured
[PT24] Configuring Analytics System...
✓ Analytics configured
[PT24] Scheduling cron jobs...
✓ Cron jobs scheduled
[PT24] Verifying deployment...
✓ Health check passed
✓ All 4 database tables created
✓ PearBlog Engine active
[PT24] Generating deployment report...
✓ Deployment report saved to: /var/www/pt24.pro/pt24-deployment-YYYYMMDD-HHMMSS.log

Deployment completed successfully! 🎉
Domain: https://pt24.pro
Admin: https://pt24.pro/wp-admin/
```

---

## ✅ POST-DEPLOYMENT VERIFICATION

### 1. Health Check
```bash
curl https://pt24.pro/wp-json/pt24/v1/health
```

**Expected Response:**
```json
{
  "status": "ok",
  "version": "2.0.0",
  "environment": "production",
  "timestamp": "2026-06-27 13:44:59",
  "checks": {
    "database": "ok",
    "uploads_writable": "ok",
    "pearblog_active": "ok",
    "openai_configured": "ok"
  }
}
```

### 2. WordPress Admin Check
```
https://pt24.pro/wp-admin/
→ PearBlog v8 → Integration Status
```

Should show:
- ✅ PT24 Core: Active
- ✅ PearBlog Engine: Active  
- ✅ LeadAI System: Enabled
- ✅ Content Linking: Enabled
- ✅ Analytics: Enabled
- ✅ All 4 database tables: Created

### 3. Database Verification
```bash
wp db query "SHOW TABLES LIKE '%pearblog%';" --allow-root
wp db query "SHOW TABLES LIKE '%pt24%';" --allow-root
```

Should return 4 tables:
- `wp_pearblog_content_meta`
- `wp_pearblog_content_links`
- `wp_pearblog_lead_attribution`
- `wp_pt24_analytics`

---

## 🔧 CONFIGURATION AFTER DEPLOYMENT

### 1. Set OpenAI API Key
Edit `/var/www/pt24.pro/.env`:
```bash
OPENAI_API_KEY=sk-your-api-key-here
OPENAI_MODEL=gpt-4o-mini
```

Then restart PHP:
```bash
systemctl restart php8.1-fpm
# or for Apache
systemctl restart apache2
```

### 2. Configure SMS Provider (SMSApi.pl)
In WordPress Admin:
```
PearBlog v8 → Lead System Configuration
```
Set:
- SMSApi Username
- SMSApi Token
- Sender Name

### 3. Configure Email Provider
```
Settings → General → Email Settings
```

Set up SMTP or third-party email service.

### 4. Seed Initial Content
```bash
wp pt24 seed-blog-topics --allow-root
wp pt24 generate-landings --allow-root
```

---

## 🎯 DEPLOYMENT CHECKLIST

Pre-Deployment:
- [ ] Server has PHP 8.1+
- [ ] MySQL 5.7+ installed
- [ ] WP-CLI available
- [ ] WordPress 6.0+ installed at /var/www/pt24.pro
- [ ] PearBlog Engine plugin available
- [ ] Backup of WordPress data taken

Deployment:
- [ ] Run deployment script
- [ ] Verify no errors
- [ ] Check deployment report

Post-Deployment:
- [ ] Health endpoint returns 'ok'
- [ ] All 4 database tables created
- [ ] WordPress admin accessible
- [ ] Integration Status page shows all green
- [ ] Set OpenAI API key
- [ ] Configure SMS provider
- [ ] Setup email provider
- [ ] Seed initial content
- [ ] Test lead capture
- [ ] Verify analytics tracking

---

## 🔍 MONITORING AFTER DEPLOYMENT

### Daily Checks
```bash
# Health endpoint
curl https://pt24.pro/wp-json/pt24/v1/health

# Lead count (last 24h)
wp db query "SELECT COUNT(*) FROM wp_poradnik_leads WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 DAY);" --allow-root

# Analytics events (last 24h)
wp db query "SELECT COUNT(*) FROM wp_pt24_analytics WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 DAY);" --allow-root
```

### Weekly Reports
```bash
# Dashboard stats
curl https://pt24.pro/wp-json/pt24/v1/dashboard/stats

# Error log
tail -f /var/www/pt24.pro/wp-content/debug.log
```

### Performance Monitoring
```bash
# Page load time
curl -w "@curl-format.txt" -o /dev/null -s https://pt24.pro/

# API response time
curl -w "@curl-format.txt" -o /dev/null -s https://pt24.pro/wp-json/pt24/v1/health

# Database query performance
wp db query "SELECT * FROM wp_pearblog_content_meta LIMIT 1;" --allow-root
```

---

## 🚨 TROUBLESHOOTING

### Issue: PHP Version Error
```bash
# Check PHP version
php -v

# Install PHP 8.1 if needed
apt install php8.1 php8.1-cli php8.1-fpm php8.1-mysql \
  php8.1-xml php8.1-mbstring php8.1-curl php8.1-zip \
  php8.1-gd php8.1-intl php8.1-bcmath
```

### Issue: MySQL Connection Error
```bash
# Check MySQL status
systemctl status mysql

# Verify credentials in wp-config.php
grep "DB_" /var/www/pt24.pro/wp-config.php

# Test connection
mysql -u wordpress -p -e "SELECT 1;"
```

### Issue: WP-CLI Not Found
```bash
# Install WP-CLI
curl -O https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar
chmod +x wp-cli.phar
sudo mv wp-cli.phar /usr/local/bin/wp
wp --version
```

### Issue: Tables Already Exist
```bash
# Check tables
wp db tables --allow-root | grep -E "pearblog|pt24"

# Drop and recreate (caution: will delete data)
wp db query "DROP TABLE IF EXISTS wp_pearblog_content_meta;" --allow-root
wp db query "DROP TABLE IF EXISTS wp_pearblog_content_links;" --allow-root
wp db query "DROP TABLE IF EXISTS wp_pearblog_lead_attribution;" --allow-root
wp db query "DROP TABLE IF EXISTS wp_pt24_analytics;" --allow-root

# Re-run deployment
./deploy-pt24-pro-enterprise.sh
```

### Issue: Plugin Activation Fails
```bash
# Check if PearBlog Engine is installed
ls /var/www/pt24.pro/wp-content/mu-plugins/pearblog-engine/

# Check error log
tail -f /var/www/pt24.pro/wp-content/debug.log

# Manually activate
wp plugin activate pearblog-engine --allow-root

# Check status
wp plugin is-active pearblog-engine --allow-root && echo "Active" || echo "Inactive"
```

---

## 📞 SUPPORT

**Documentation:**
- Main: `PT24-ENTERPRISE-INTEGRATION-COMPLETE.md`
- Summary: `PT24-ENTERPRISE-FINAL-SUMMARY.md`
- Execution: `DEPLOYMENT-PT24-EXECUTION.sh`

**GitHub:**
- Repository: https://github.com/AndyPearman89/PearBlog-Engine-
- Branch: andypearman89-curly-fishstick
- Issues: https://github.com/AndyPearman89/PearBlog-Engine-/issues

**Logs:**
- Deployment: `/var/www/pt24.pro/pt24-deployment-*.log`
- WordPress: `/var/www/pt24.pro/wp-content/debug.log`

---

## 🎉 SUCCESS!

Your PT24.pro Enterprise system is now deployed and running!

**Next Steps:**
1. ✅ Configure API keys
2. ✅ Setup SMS provider
3. ✅ Seed content
4. ✅ Start capturing leads
5. ✅ Monitor analytics

**Dashboard:** https://pt24.pro/wp-admin/
**Health:** https://pt24.pro/wp-json/pt24/v1/health

🚀 **Ready to scale!**
