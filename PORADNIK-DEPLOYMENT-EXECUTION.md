# 🚀 Poradnik.pro Deployment Execution Guide

> **PearBlog Engine v8.0.0 - Production Deployment**
> **Target Domain:** poradnik.pro
> **Server:** 204.48.27.118
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

- [ ] **A Record:** `poradnik.pro` → 204.48.27.118
- [ ] **A Record:** `www.poradnik.pro` → 204.48.27.118
- [ ] **DNS Propagation:** Completed (verify with `dig poradnik.pro`)

```bash
# Verify DNS propagation:
dig poradnik.pro +short
# Expected: 204.48.27.118

dig www.poradnik.pro +short
# Expected: 204.48.27.118
```

### 🔑 API Keys (Optional but Recommended)

Prepare these API keys for full functionality:

- [ ] **OpenAI API Key** - For AI content generation (GPT-4o-mini)
- [ ] **Google AdSense Publisher ID** - For monetization
- [ ] **Slack Webhook URL** - For monitoring alerts (optional)
- [ ] **Discord Webhook URL** - For monitoring alerts (optional)

---

## 🚀 Deployment Methods

### Method 1: Automated One-Line Deployment (Recommended)

**Duration:** 15-20 minutes

This fully automated script will:
- Install all required software (PHP 8.1+, MySQL/MariaDB, WP-CLI)
- Download and configure WordPress
- Install PearBlog Engine v8.0.0
- Configure database and security
- Set up SSL certificate with Let's Encrypt
- Seed initial content topics
- Enable Poradnik Clean Content System

```bash
# Step 1: SSH to your server
ssh root@204.48.27.118

# Step 2: Run automated deployment
curl -sL https://raw.githubusercontent.com/AndyPearman89/PearBlog-Engine-/main/scripts/deploy-poradnik-pro.sh | bash
```

**What the script does:**
1. ✅ Pre-flight checks (OS, root access)
2. ✅ System package updates
3. ✅ PHP 8.1 + extensions installation
4. ✅ MySQL/MariaDB database setup
5. ✅ Apache/Nginx web server configuration
6. ✅ WP-CLI installation
7. ✅ Database creation (`poradnik_pro`)
8. ✅ WordPress download and installation
9. ✅ **PearBlog Engine v8.0.0 deployment**
10. ✅ PearBlog Theme installation
11. ✅ WordPress configuration
12. ✅ Web server virtual host setup
13. ✅ SSL certificate (Let's Encrypt)
14. ✅ Cron job configuration
15. ✅ File permissions setup
16. ✅ Initial content seeding (10 topics)
17. ✅ **Poradnik Clean Content System activation**
18. ✅ Summary and credentials display

**After deployment completes, the script will display:**
- Admin credentials (save these securely!)
- Database credentials
- PearBlog API key
- Next steps

---

### Method 2: Step-by-Step Manual Deployment

If you prefer manual control, follow these detailed steps:

#### Step 1: Connect to Server

```bash
ssh root@204.48.27.118
```

#### Step 2: Update System

```bash
apt update && apt upgrade -y
```

#### Step 3: Install PHP 8.1+ and Extensions

```bash
# Add PHP repository
apt install -y software-properties-common
add-apt-repository -y ppa:ondrej/php
apt update

# Install PHP and extensions
apt install -y php8.1 php8.1-cli php8.1-fpm \
    php8.1-mysql php8.1-xml php8.1-mbstring \
    php8.1-curl php8.1-zip php8.1-gd php8.1-intl \
    php8.1-bcmath php8.1-soap

# Verify installation
php -v
# Expected: PHP 8.1.x or higher
```

#### Step 4: Install MySQL/MariaDB

```bash
# Install MariaDB
apt install -y mariadb-server mariadb-client

# Start and enable service
systemctl start mariadb
systemctl enable mariadb

# Secure installation (set root password)
mysql_secure_installation
```

#### Step 5: Install Web Server (Apache or Nginx)

**Option A: Apache**
```bash
apt install -y apache2 libapache2-mod-php8.1
a2enmod rewrite ssl headers
systemctl restart apache2
```

**Option B: Nginx**
```bash
apt install -y nginx
systemctl start nginx
systemctl enable nginx
```

#### Step 6: Install WP-CLI

```bash
curl -O https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar
chmod +x wp-cli.phar
mv wp-cli.phar /usr/local/bin/wp

# Verify
wp --version --allow-root
```

#### Step 7: Create Database

```bash
mysql -u root -p <<EOF
CREATE DATABASE poradnik_pro CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'poradnik_user'@'localhost' IDENTIFIED BY 'STRONG_PASSWORD_HERE';
GRANT ALL PRIVILEGES ON poradnik_pro.* TO 'poradnik_user'@'localhost';
FLUSH PRIVILEGES;
EOF
```

#### Step 8: Install WordPress

```bash
# Create directory
mkdir -p /var/www/poradnik.pro
cd /var/www/poradnik.pro

# Download WordPress
wp core download --allow-root

# Create wp-config.php
wp config create \
    --dbname=poradnik_pro \
    --dbuser=poradnik_user \
    --dbpass=STRONG_PASSWORD_HERE \
    --dbhost=localhost \
    --allow-root

# Install WordPress
wp core install \
    --url=https://poradnik.pro \
    --title="Poradnik.pro - Porady i przewodniki" \
    --admin_user=admin \
    --admin_password=ADMIN_PASSWORD_HERE \
    --admin_email=admin@poradnik.pro \
    --allow-root
```

#### Step 9: Install PearBlog Engine v8.0.0

```bash
cd /var/www/poradnik.pro/wp-content

# Create mu-plugins directory
mkdir -p mu-plugins
cd mu-plugins

# Download PearBlog Engine v8.0.0
wget https://github.com/AndyPearman89/PearBlog-Engine-/archive/refs/tags/v8.0.0.tar.gz
tar -xzf v8.0.0.tar.gz
mv PearBlog-Engine--8.0.0/mu-plugins/pearblog-engine ./
rm -rf PearBlog-Engine--8.0.0 v8.0.0.tar.gz

# Install Composer dependencies
cd pearblog-engine
composer install --no-dev --optimize-autoloader
```

#### Step 10: Install PearBlog Theme

```bash
cd /var/www/poradnik.pro/wp-content/themes
cp -r ../mu-plugins/pearblog-engine/theme/pearblog-theme ./

# Activate theme
wp theme activate pearblog-theme --allow-root
```

#### Step 11: Configure PearBlog

Add to `/var/www/poradnik.pro/wp-config.php`:

```php
/* PearBlog Engine v8.0 Configuration */
define('PEARBLOG_OPENAI_API_KEY', 'sk-proj-YOUR_KEY_HERE');

/* WordPress Settings */
define('WP_MEMORY_LIMIT', '512M');
define('WP_MAX_MEMORY_LIMIT', '512M');
define('DISALLOW_FILE_EDIT', true);
```

Configure via WP-CLI:

```bash
cd /var/www/poradnik.pro

# Core settings
wp option update pearblog_industry 'poradnik' --allow-root
wp option update pearblog_tone 'professional' --allow-root
wp option update pearblog_language 'pl' --allow-root
wp option update pearblog_publish_rate 2 --allow-root

# Enable Poradnik Clean Content System (auto-activated for 'poradnik' industry)
wp option update pearblog_homepage_version 'v7' --allow-root
```

#### Step 12: Set Up SSL Certificate

```bash
# Install Certbot
apt install -y certbot python3-certbot-apache
# or for Nginx:
apt install -y certbot python3-certbot-nginx

# Obtain certificate
certbot --apache -d poradnik.pro -d www.poradnik.pro
# or for Nginx:
certbot --nginx -d poradnik.pro -d www.poradnik.pro
```

#### Step 13: Configure Cron

```bash
# Add WordPress cron to system crontab
(crontab -l 2>/dev/null; echo "0 * * * * cd /var/www/poradnik.pro && /usr/local/bin/wp cron event run --due-now --allow-root >/dev/null 2>&1") | crontab -
```

#### Step 14: Set Permissions

```bash
chown -R www-data:www-data /var/www/poradnik.pro
find /var/www/poradnik.pro -type d -exec chmod 755 {} \;
find /var/www/poradnik.pro -type f -exec chmod 644 {} \;
```

#### Step 15: Seed Initial Content

```bash
cd /var/www/poradnik.pro

# Add Poradnik-specific topics
wp pearblog queue add "Jak wybrać wykonawcę remontowego" --allow-root
wp pearblog queue add "Ile kosztuje remont mieszkania" --allow-root
wp pearblog queue add "Budowa domu krok po kroku" --allow-root
wp pearblog queue add "Porady dotyczące remontów" --allow-root
wp pearblog queue add "Jak zaplanować remont łazienki" --allow-root
wp pearblog queue add "Wybór materiałów budowlanych" --allow-root
wp pearblog queue add "Porady dla majsterkowiczów" --allow-root
wp pearblog queue add "Remont kuchni - przewodnik" --allow-root
wp pearblog queue add "Jak wybrać fachowca" --allow-root
wp pearblog queue add "Porady remontowe dla początkujących" --allow-root
```

---

## 📝 Post-Deployment Steps

### 1. Verify Installation

```bash
# Check website is accessible:
curl -I https://poradnik.pro
# Expected: HTTP/2 200

# Check PearBlog health endpoint:
curl https://poradnik.pro/wp-json/pearblog/v1/health | jq
# Expected: {"status":"ok","api_key_set":true,...}
```

### 2. Test Content Generation

```bash
cd /var/www/poradnik.pro

# Generate test article:
wp pearblog generate --allow-root

# Check queue status:
wp pearblog queue list --allow-root

# View stats:
wp pearblog stats --allow-root
```

### 3. Configure Monetization (Optional)

```bash
# Set Google AdSense Publisher ID:
wp option update pearblog_adsense_publisher_id 'ca-pub-YOUR_ID' --allow-root

# Enable AdSense:
wp option update pearblog_adsense_enabled 1 --allow-root

# Set AdSense strategy (funnel_aware recommended):
wp option update pearblog_adsense_strategy 'funnel_aware' --allow-root
```

### 4. Enable Autopilot Mode

```bash
# Start autonomous content generation:
wp pearblog autopilot start --allow-root

# Check autopilot status:
wp pearblog autopilot status --allow-root

# Expected output:
# Autopilot Status: ACTIVE
# Generate Rate: 2 articles/hour
# Queue Size: 10 topics
```

### 5. Verify Poradnik Clean Content System

```bash
# Check that Poradnik prompt builder is active:
wp pearblog stats --allow-root | grep "Prompt Builder"
# Expected: PoradnikPromptBuilder

# The system will automatically generate articles with:
# - Intro section
# - "Ile kosztuje?" (Cost analysis)
# - "Od czego zależy?" (Price factors)
# - "Jak wybrać?" (Selection advice)
# - Soft CTA
# - FAQ section
```

---

## 🔍 Post-Deployment Verification

### Health Checks

Run these commands to verify everything is working:

```bash
# 1. WordPress is accessible:
curl -I https://poradnik.pro
# Expected: HTTP/2 200

# 2. Admin panel is accessible:
curl -I https://poradnik.pro/wp-admin
# Expected: HTTP/2 302 (redirect to login)

# 3. PearBlog API health:
curl https://poradnik.pro/wp-json/pearblog/v1/health
# Expected: {"status":"ok",...}

# 4. PHP version:
php -v
# Expected: PHP 8.1.x or higher

# 5. WP-CLI is working:
cd /var/www/poradnik.pro && wp cli version --allow-root
# Expected: WP-CLI 2.x.x

# 6. Database connectivity:
cd /var/www/poradnik.pro && wp db check --allow-root
# Expected: Success: Database connection verified.

# 7. Cron events are scheduled:
wp cron event list --allow-root | grep pearblog
# Expected: pearblog_pipeline_cron listed

# 8. First article generated:
wp post list --post_type=post --allow-root
# Expected: At least 1 post listed
```

### Security Audit

```bash
# Run security audit (v8.0.0 feature):
cd /var/www/poradnik.pro
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
certbot --apache -d poradnik.pro -d www.poradnik.pro

# If DNS not propagated, use DNS challenge:
certbot --apache -d poradnik.pro --manual --preferred-challenges dns
```

### Issue: WordPress White Screen

```bash
# Enable debug mode:
nano /var/www/poradnik.pro/wp-config.php

# Add these lines:
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);

# Check debug log:
tail -f /var/www/poradnik.pro/wp-content/debug.log
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

### Issue: Poradnik Content System Not Active

```bash
# Verify industry setting:
wp option get pearblog_industry --allow-root
# Expected: poradnik (or guide, remont, budowa, home services)

# If incorrect, update:
wp option update pearblog_industry 'poradnik' --allow-root

# Regenerate an article to test:
wp pearblog generate --allow-root
```

---

## 📊 Monitoring & Maintenance

### Daily Health Check

```bash
# Quick health check script:
#!/bin/bash
echo "=== Poradnik.pro Daily Health Check ==="
echo ""
echo "1. Website Status:"
curl -Is https://poradnik.pro | head -1
echo ""
echo "2. PearBlog Health:"
curl -s https://poradnik.pro/wp-json/pearblog/v1/health | jq -r '.status'
echo ""
echo "3. Queue Size:"
cd /var/www/poradnik.pro && wp pearblog stats --allow-root | grep "Queue"
echo ""
echo "4. Recent Errors:"
tail -20 /var/www/poradnik.pro/wp-content/debug.log | grep -i error || echo "No errors"
```

### Weekly Maintenance

```bash
# Run weekly maintenance:
#!/bin/bash
cd /var/www/poradnik.pro

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
- **DEPLOYMENT-poradnik-pro.md** - Poradnik.pro specific deployment
- **QUICKSTART-poradnik-pro.md** - 5-minute quick start guide
- **PROGRESS-VISUALIZATION.md** - Current project status
- **TEST-RUN-REPORT-2026-05-05.md** - Latest test results
- **SECURITY-AUDIT-REPORT-DETAILED.md** - Security audit report
- **PORADNIK-CLEAN-CONTENT-SYSTEM.md** - Poradnik content system documentation

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

- [ ] Website is accessible at https://poradnik.pro
- [ ] SSL certificate is installed and auto-renewing
- [ ] Admin panel login works (save credentials!)
- [ ] PearBlog health endpoint returns "ok"
- [ ] OpenAI API key is configured
- [ ] Content generation test successful
- [ ] Poradnik Clean Content System is active
- [ ] Autopilot mode started
- [ ] Cron jobs are running
- [ ] Monitoring alerts configured
- [ ] Security audit passed (Risk Score < 20)
- [ ] Backup system configured
- [ ] DNS fully propagated
- [ ] Performance tested (load time < 2s)
- [ ] Mobile responsiveness verified

---

## 🎉 Success!

Your Poradnik.pro deployment is complete! The platform is now:

✅ **Live** at https://poradnik.pro
✅ **Secured** with SSL and OWASP compliance
✅ **Automated** with AI content generation
✅ **Optimized** with Poradnik Clean Content System
✅ **Monitored** with health checks and alerts
✅ **Scalable** and ready for production traffic

**Next Phase:** Monitor initial 24 hours and optimize based on real traffic data.

---

*PearBlog Engine v8.0.0 — Enterprise-ready autonomous content platform*
*Deployment Guide v1.0 — 2026-05-05*
*Poradnik.pro - Polish advice and guides platform*
