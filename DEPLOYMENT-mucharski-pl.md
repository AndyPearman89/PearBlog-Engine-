# 🚀 Deployment Guide: mucharski.pl

**Domain:** mucharski.pl (zalew.mucharski.pl)
**Server:** TBD - Update with actual server IP
**User:** root
**Target:** Production deployment of PearBlog Engine v6.0
**Industry:** Water sports and fishing (Sporty wodne i wędkarstwo)

---

## Quick Deploy Commands

```bash
# From your local machine:
ssh root@YOUR_SERVER_IP

# Once connected, run:
curl -sL https://raw.githubusercontent.com/AndyPearman89/PearBlog-Engine-/main/scripts/deploy-mucharski-pl.sh | bash
```

**⚠️ Note:** Before running the script, update `SERVER_IP` in `deploy-mucharski-pl.sh` with your actual server IP address.

---

## Table of Contents

1. [Prerequisites Check](#1-prerequisites-check)
2. [Initial Server Setup](#2-initial-server-setup)
3. [WordPress Installation](#3-wordpress-installation)
4. [PearBlog Engine Deployment](#4-pearblog-engine-deployment)
5. [Configuration](#5-configuration)
6. [SSL Setup](#6-ssl-setup)
7. [Testing & Verification](#7-testing--verification)
8. [Go Live](#8-go-live)
9. [Monitoring](#9-monitoring)
10. [Troubleshooting](#10-troubleshooting)

---

## 1. Prerequisites Check

### Server Access
```bash
# Test SSH access:
ssh root@YOUR_SERVER_IP

# Should connect successfully
```

### Required Software
```bash
# Check PHP version (need ≥8.1):
php -v

# Check MySQL/MariaDB:
mysql --version

# Check Apache/Nginx:
apache2 -v  # or
nginx -v

# Install WP-CLI if not present:
curl -O https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar
chmod +x wp-cli.phar
mv wp-cli.phar /usr/local/bin/wp
```

### DNS Verification
```bash
# Verify DNS is pointing to server:
dig mucharski.pl +short
# Should return: YOUR_SERVER_IP

nslookup mucharski.pl
```

---

## 2. Initial Server Setup

### Install Required PHP Extensions
```bash
# Connect to server:
ssh root@YOUR_SERVER_IP

# Update system:
apt update && apt upgrade -y

# Install PHP 8.1+ and extensions:
apt install -y php8.1 php8.1-cli php8.1-fpm php8.1-mysql \
  php8.1-curl php8.1-json php8.1-mbstring php8.1-xml \
  php8.1-zip php8.1-gd php8.1-intl php8.1-openssl

# Optional performance extensions:
apt install -y php8.1-redis php8.1-imagick php8.1-apcu

# Verify installation:
php -m | grep -E 'curl|json|mbstring|xml|zip|gd|intl|openssl'
```

### Configure PHP
```bash
# Edit php.ini:
nano /etc/php/8.1/fpm/php.ini

# Update these values:
memory_limit = 512M
max_execution_time = 300
upload_max_filesize = 64M
post_max_size = 64M

# Restart PHP-FPM:
systemctl restart php8.1-fpm
```

### Create Directory Structure
```bash
# Create WordPress directory:
mkdir -p /var/www/mucharski.pl
chown www-data:www-data /var/www/mucharski.pl
```

---

## 3. WordPress Installation

### Download WordPress
```bash
cd /var/www/mucharski.pl
wp core download --allow-root
```

### Database Setup
```bash
# Login to MySQL:
mysql -u root -p

# Create database and user:
CREATE DATABASE mucharski_pl CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'mucharski_user'@'localhost' IDENTIFIED BY 'STRONG_PASSWORD_HERE';
GRANT ALL PRIVILEGES ON mucharski_pl.* TO 'mucharski_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

### Configure WordPress
```bash
cd /var/www/mucharski.pl

# Create wp-config.php:
wp config create \
  --dbname=mucharski_pl \
  --dbuser=mucharski_user \
  --dbpass=YOUR_DB_PASSWORD \
  --dbhost=localhost \
  --dbcharset=utf8mb4 \
  --dbcollate=utf8mb4_unicode_ci \
  --allow-root

# Set WordPress constants:
wp config set WP_MEMORY_LIMIT "512M" --allow-root
wp config set DISABLE_WP_CRON false --raw --allow-root
wp config set WP_DEBUG false --raw --allow-root
wp config set table_prefix "mch_" --allow-root
```

### Install WordPress
```bash
wp core install \
  --url="http://mucharski.pl" \
  --title="Mucharski.pl - Zalew, Wędkarstwo, Sporty Wodne" \
  --admin_user="admin" \
  --admin_password="STRONG_ADMIN_PASSWORD" \
  --admin_email="your-email@example.com" \
  --allow-root

# Set permalink structure:
wp rewrite structure '/%postname%/' --allow-root
wp rewrite flush --allow-root
```

---

## 4. PearBlog Engine Deployment

### Clone Repository
```bash
cd /tmp
git clone https://github.com/AndyPearman89/PearBlog-Engine-.git
cd PearBlog-Engine-
```

### Deploy MU-Plugin
```bash
# Create mu-plugins directory:
mkdir -p /var/www/mucharski.pl/wp-content/mu-plugins

# Copy PearBlog Engine:
cp -r mu-plugins/pearblog-engine /var/www/mucharski.pl/wp-content/mu-plugins/

# Set correct permissions:
chown -R www-data:www-data /var/www/mucharski.pl/wp-content/mu-plugins/pearblog-engine
```

### Deploy Theme
```bash
# Copy theme:
cp -r theme/pearblog-theme /var/www/mucharski.pl/wp-content/themes/

# Activate theme:
cd /var/www/mucharski.pl
wp theme activate pearblog-theme --allow-root

# Set permissions:
chown -R www-data:www-data /var/www/mucharski.pl/wp-content/themes/pearblog-theme
```

---

## 5. Configuration

### Configure PearBlog Engine
```bash
cd /var/www/mucharski.pl

# Set OpenAI API key:
wp option update pearblog_openai_api_key "sk-proj-YOUR_KEY_HERE" --allow-root

# Configure industry and niche:
wp option update pearblog_industry "sporty wodne i wędkarstwo" --allow-root
wp option update pearblog_tone "praktyczny, pomocny, dla pasjonatów wędkarstwa i sportów wodnych" --allow-root

# Set content settings:
wp option update pearblog_publish_rate "0.5" --allow-root  # 1 article every 2 hours
wp option update pearblog_language "pl" --allow-root
wp option update pearblog_ai_images_enabled "1" --allow-root

# Enable autonomous mode:
wp option update pearblog_autonomous_mode "1" --allow-root
```

### Add Initial Content Topics
```bash
# Add fishing and water sports topics:
wp pearblog queue add "Najlepsze miejsca na wędkarstwo w Polsce" --allow-root
wp pearblog queue add "Jak złowić karpia - poradnik dla początkujących" --allow-root
wp pearblog queue add "10 najważniejszych sprzętów wędkarskich" --allow-root
wp pearblog queue add "Sporty wodne na polskich jeziorach" --allow-root
wp pearblog queue add "Wakeboarding dla początkujących - od czego zacząć" --allow-root
wp pearblog queue add "Jak przygotować przynętę na karpia" --allow-root
wp pearblog queue add "Najlepsze kajaki turystyczne 2026" --allow-root
wp pearblog queue add "Wędkarstwo muchowe - kompletny przewodnik" --allow-root
wp pearblog queue add "Bezpieczeństwo na wodzie - co musisz wiedzieć" --allow-root
wp pearblog queue add "Stand up paddle (SUP) - poradnik dla początkujących" --allow-root

# Verify queue:
wp pearblog queue list --allow-root
```

### GitHub Secrets Configuration

Go to: https://github.com/AndyPearman89/PearBlog-Engine-
Navigate to: **Settings** → **Secrets and variables** → **Actions**

Add these secrets:

**Name:** MUCHARSKI_SSH_HOST
**Value:** YOUR_SERVER_IP

**Name:** MUCHARSKI_SSH_USER
**Value:** root

**Name:** MUCHARSKI_SSH_PRIVATE_KEY
**Value:** [Your SSH private key content]

**Name:** MUCHARSKI_WP_PATH
**Value:** /var/www/mucharski.pl

**Name:** MUCHARSKI_ROOT_PASSWORD
**Value:** [MySQL root password]

---

## 6. SSL Setup

### Install Certbot
```bash
apt install -y certbot python3-certbot-apache  # for Apache
# OR
apt install -y certbot python3-certbot-nginx   # for Nginx
```

### Obtain SSL Certificate
```bash
# For Apache:
certbot --apache -d mucharski.pl -d www.mucharski.pl \
  --email your-email@example.com \
  --agree-tos \
  --non-interactive \
  --redirect

# For Nginx:
certbot --nginx -d mucharski.pl -d www.mucharski.pl \
  --email your-email@example.com \
  --agree-tos \
  --non-interactive \
  --redirect
```

### Update WordPress URLs
```bash
cd /var/www/mucharski.pl
wp option update home "https://mucharski.pl" --allow-root
wp option update siteurl "https://mucharski.pl" --allow-root
```

### Test SSL Auto-Renewal
```bash
certbot renew --dry-run
```

---

## 7. Testing & Verification

### Test Content Generation
```bash
cd /var/www/mucharski.pl

# Generate first article:
wp pearblog generate --allow-root

# Check statistics:
wp pearblog stats --allow-root

# View queue:
wp pearblog queue list --allow-root
```

### Test Health Endpoint
```bash
curl https://mucharski.pl/wp-json/pearblog/v1/health
# Should return: {"status":"ok","timestamp":...}
```

### Verify Autonomous Mode
```bash
# Check autonomous mode status:
wp option get pearblog_autonomous_mode --allow-root
# Should return: 1

# Check scheduled cron:
wp cron event list --allow-root | grep pearblog
```

### Start Autopilot
```bash
# Start Enterprise Autopilot:
wp pearblog autopilot start --allow-root

# Check autopilot status:
wp pearblog autopilot status --allow-root
```

---

## 8. Go Live

### Final Checks
```bash
# Verify site is accessible:
curl -I https://mucharski.pl
# Should return: HTTP/2 200

# Check WordPress admin:
# Visit: https://mucharski.pl/wp-admin
# Login with admin credentials

# Check PearBlog Engine dashboard:
# Visit: https://mucharski.pl/wp-admin/admin.php?page=pearblog-engine
```

### Performance Optimization
```bash
# Enable object caching (if Redis installed):
wp plugin install redis-cache --activate --allow-root
wp redis enable --allow-root

# Enable WP-Cron (should already be enabled):
wp config get DISABLE_WP_CRON --allow-root
```

---

## 9. Monitoring

### Check Logs
```bash
# PearBlog Engine logs:
tail -f /var/www/mucharski.pl/wp-content/pearblog-engine.log

# WordPress debug log:
tail -f /var/www/mucharski.pl/wp-content/debug.log

# Apache/Nginx error logs:
tail -f /var/log/apache2/error.log  # Apache
tail -f /var/log/nginx/error.log    # Nginx
```

### Monitor Cron Jobs
```bash
# List all cron events:
wp cron event list --allow-root

# Test cron manually:
wp cron event run pearblog_content_pipeline --allow-root
```

### Check API Costs
```bash
# View OpenAI API costs:
wp pearblog stats --allow-root

# Check total cost:
wp option get pearblog_ai_cost_cents --allow-root
```

### Monitor Autopilot
```bash
# Check autopilot status:
wp pearblog autopilot status --allow-root

# View autopilot progress:
wp option get pearblog_autopilot_state --format=json --allow-root
```

---

## 10. Troubleshooting

### Content Not Generating

**Problem:** Articles are not being generated automatically.

**Solution:**
```bash
# Check autonomous mode:
wp option get pearblog_autonomous_mode --allow-root

# Enable if disabled:
wp option update pearblog_autonomous_mode "1" --allow-root

# Check cron:
wp cron event list --allow-root | grep pearblog

# Run manually:
wp pearblog generate --allow-root
```

### OpenAI API Errors

**Problem:** API requests failing.

**Solution:**
```bash
# Verify API key:
wp option get pearblog_openai_api_key --allow-root

# Check circuit breaker status:
wp pearblog stats --allow-root

# Reset circuit breaker if needed:
wp pearblog circuit reset --allow-root

# Test API manually:
curl https://api.openai.com/v1/models \
  -H "Authorization: Bearer YOUR_KEY_HERE"
```

### Permission Issues

**Problem:** Files cannot be written.

**Solution:**
```bash
# Fix permissions:
chown -R www-data:www-data /var/www/mucharski.pl/wp-content
chmod -R 755 /var/www/mucharski.pl/wp-content

# Check current permissions:
ls -la /var/www/mucharski.pl/wp-content/
```

### Database Connection Errors

**Problem:** WordPress cannot connect to database.

**Solution:**
```bash
# Test MySQL connection:
mysql -u mucharski_user -p mucharski_pl

# Check wp-config.php settings:
wp config get DB_NAME --allow-root
wp config get DB_USER --allow-root
wp config get DB_HOST --allow-root

# Verify database exists:
mysql -u root -p -e "SHOW DATABASES;"
```

### SSL Certificate Issues

**Problem:** SSL not working or expired.

**Solution:**
```bash
# Check certificate status:
certbot certificates

# Renew certificate:
certbot renew

# Test renewal process:
certbot renew --dry-run
```

---

## Post-Deployment Checklist

- [ ] Server accessible via SSH
- [ ] PHP 8.1+ installed with all extensions
- [ ] MySQL database created and configured
- [ ] WordPress installed and accessible
- [ ] PearBlog Engine MU-plugin deployed
- [ ] Theme activated
- [ ] OpenAI API key configured
- [ ] Initial topics added to queue
- [ ] SSL certificate installed
- [ ] Autonomous mode enabled
- [ ] First article generated successfully
- [ ] Health endpoint responding
- [ ] Cron jobs scheduled
- [ ] Autopilot started
- [ ] Monitoring logs checked
- [ ] GitHub Secrets configured

---

## Quick Reference Commands

```bash
# SSH to server:
ssh root@YOUR_SERVER_IP

# Navigate to WordPress:
cd /var/www/mucharski.pl

# Generate article:
wp pearblog generate --allow-root

# Check stats:
wp pearblog stats --allow-root

# View queue:
wp pearblog queue list --allow-root

# Add topic:
wp pearblog queue add "Your topic here" --allow-root

# Check logs:
tail -f wp-content/pearblog-engine.log

# Autopilot status:
wp pearblog autopilot status --allow-root

# Health check:
curl https://mucharski.pl/wp-json/pearblog/v1/health
```

---

**Last Updated:** 2026-05-02
**Version:** 6.0.0
**Industry:** Water sports and fishing
**Publish Rate:** 0.5/hour (1 article every 2 hours)
**Language:** Polish (pl)
