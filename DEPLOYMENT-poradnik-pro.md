# 🚀 Deployment Guide: poradnik.pro

**Domain:** poradnik.pro
**Server:** 204.48.27.118
**User:** root
**Target:** Production deployment of PearBlog Engine v6.0

---

## Quick Deploy Commands

```bash
# From your local machine:
ssh root@204.48.27.118

# Once connected, run:
curl -sL https://raw.githubusercontent.com/AndyPearman89/PearBlog-Engine-/main/scripts/deploy-poradnik-pro.sh | bash
```

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
ssh root@204.48.27.118

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
dig poradnik.pro +short
# Should return: 204.48.27.118

nslookup poradnik.pro
```

---

## 2. Initial Server Setup

### Install Required PHP Extensions
```bash
# Connect to server:
ssh root@204.48.27.118

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

# Set these values:
memory_limit = 512M
max_execution_time = 300
upload_max_filesize = 64M
post_max_size = 64M
opcache.enable = 1
opcache.memory_consumption = 256

# Restart PHP-FPM:
systemctl restart php8.1-fpm
```

### Install MySQL/MariaDB
```bash
# If not already installed:
apt install -y mariadb-server

# Secure installation:
mysql_secure_installation

# Create database for poradnik.pro:
mysql -u root -p <<EOF
CREATE DATABASE poradnik_pro CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'poradnik_user'@'localhost' IDENTIFIED BY 'STRONG_PASSWORD_HERE';
GRANT ALL PRIVILEGES ON poradnik_pro.* TO 'poradnik_user'@'localhost';
FLUSH PRIVILEGES;
EOF
```

---

## 3. WordPress Installation

### Create Web Directory
```bash
# Create directory structure:
mkdir -p /var/www/poradnik.pro
cd /var/www/poradnik.pro

# Download WordPress:
wget https://wordpress.org/latest.tar.gz
tar -xzf latest.tar.gz
mv wordpress/* .
rmdir wordpress
rm latest.tar.gz

# Set ownership:
chown -R www-data:www-data /var/www/poradnik.pro
```

### Configure wp-config.php
```bash
# Copy sample config:
cp wp-config-sample.php wp-config.php

# Edit configuration:
nano wp-config.php
```

Add these settings:
```php
<?php
// Database Configuration
define( 'DB_NAME', 'poradnik_pro' );
define( 'DB_USER', 'poradnik_user' );
define( 'DB_PASSWORD', 'STRONG_PASSWORD_HERE' );
define( 'DB_HOST', 'localhost' );
define( 'DB_CHARSET', 'utf8mb4' );
define( 'DB_COLLATE', 'utf8mb4_unicode_ci' );

// Change table prefix for security:
$table_prefix = 'prd_';

// PearBlog Engine Requirements
define( 'WP_MEMORY_LIMIT', '512M' );
define( 'DISABLE_WP_CRON', false );
define( 'WP_CLI_ALLOW_ROOT', true );

// Production settings
define( 'WP_DEBUG', false );
define( 'WP_DEBUG_LOG', false );
define( 'WP_DEBUG_DISPLAY', false );

// Security keys - generate at https://api.wordpress.org/secret-key/1.1/salt/
define('AUTH_KEY',         'put your unique phrase here');
define('SECURE_AUTH_KEY',  'put your unique phrase here');
define('LOGGED_IN_KEY',    'put your unique phrase here');
define('NONCE_KEY',        'put your unique phrase here');
define('AUTH_SALT',        'put your unique phrase here');
define('SECURE_AUTH_SALT', 'put your unique phrase here');
define('LOGGED_IN_SALT',   'put your unique phrase here');
define('NONCE_SALT',       'put your unique phrase here');

// That's all, stop editing!
if ( ! defined( 'ABSPATH' ) ) {
    define( 'ABSPATH', __DIR__ . '/' );
}
require_once ABSPATH . 'wp-settings.php';
```

### Web Server Configuration

#### For Apache:
```bash
# Create VirtualHost:
nano /etc/apache2/sites-available/poradnik.pro.conf
```

```apache
<VirtualHost *:80>
    ServerName poradnik.pro
    ServerAlias www.poradnik.pro
    DocumentRoot /var/www/poradnik.pro

    <Directory /var/www/poradnik.pro>
        Options FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/poradnik.pro-error.log
    CustomLog ${APACHE_LOG_DIR}/poradnik.pro-access.log combined
</VirtualHost>
```

```bash
# Enable site and modules:
a2ensite poradnik.pro.conf
a2enmod rewrite
systemctl restart apache2
```

#### For Nginx:
```bash
# Create server block:
nano /etc/nginx/sites-available/poradnik.pro
```

```nginx
server {
    listen 80;
    server_name poradnik.pro www.poradnik.pro;
    root /var/www/poradnik.pro;
    index index.php index.html;

    location / {
        try_files $uri $uri/ /index.php?$args;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }

    location ~ /\.ht {
        deny all;
    }
}
```

```bash
# Enable site:
ln -s /etc/nginx/sites-available/poradnik.pro /etc/nginx/sites-enabled/
nginx -t
systemctl restart nginx
```

### Complete WordPress Installation
```bash
# Install WordPress via WP-CLI:
cd /var/www/poradnik.pro
wp core install \
  --url="http://poradnik.pro" \
  --title="Poradnik.pro" \
  --admin_user="admin" \
  --admin_password="ADMIN_PASSWORD_HERE" \
  --admin_email="admin@poradnik.pro" \
  --allow-root

# Set permalink structure:
wp rewrite structure '/%postname%/' --allow-root
wp rewrite flush --allow-root
```

---

## 4. PearBlog Engine Deployment

### Method A: Direct Git Clone
```bash
cd /tmp
git clone https://github.com/AndyPearman89/PearBlog-Engine-.git
cd PearBlog-Engine-

# Deploy MU-plugin:
mkdir -p /var/www/poradnik.pro/wp-content/mu-plugins
cp -r mu-plugins/pearblog-engine /var/www/poradnik.pro/wp-content/mu-plugins/

# Deploy theme:
cp -r theme/pearblog-theme /var/www/poradnik.pro/wp-content/themes/

# Set ownership:
chown -R www-data:www-data /var/www/poradnik.pro/wp-content/mu-plugins/pearblog-engine
chown -R www-data:www-data /var/www/poradnik.pro/wp-content/themes/pearblog-theme

# Activate theme:
cd /var/www/poradnik.pro
wp theme activate pearblog-theme --allow-root

# Cleanup:
rm -rf /tmp/PearBlog-Engine-
```

### Method B: GitHub Actions (Automated)

Set up these GitHub Secrets in your repository:

```
Settings → Secrets and variables → Actions → New repository secret

Name: SSH_HOST
Value: 204.48.27.118

Name: SSH_USER
Value: root

Name: SSH_PRIVATE_KEY
Value: [contents of ~/.ssh/id_ed25519 private key]

Name: WP_PATH
Value: /var/www/poradnik.pro

Name: SSH_PORT (optional)
Value: 22
```

Then push to main branch or manually trigger workflow:
```bash
# From local development machine:
git push origin main

# Or manually trigger via GitHub Actions UI
```

---

## 5. Configuration

### Configure PearBlog Engine

#### Via WP Admin UI:
```
1. Visit: https://poradnik.pro/wp-admin
2. Navigate to: PearBlog Engine → General
3. Configure:
   - OpenAI API Key: sk-proj-xxxxxxxxxxxx
   - Industry: "poradniki praktyczne"
   - Content Tone: "praktyczny, pomocny, przystępny"
   - Publish Rate: 1 (1 article per hour)
   - Language: pl
   - Autonomous Mode: ON
```

#### Via wp-config.php (Alternative):
```php
// Add to wp-config.php:
define( 'PEARBLOG_OPENAI_API_KEY', 'sk-proj-xxxxxxxxxxxx' );
define( 'PEARBLOG_INDUSTRY', 'poradniki' );
define( 'PEARBLOG_TONE', 'praktyczny, pomocny' );
define( 'PEARBLOG_LANGUAGE', 'pl' );
define( 'PEARBLOG_PUBLISH_RATE', 1 );
```

#### Via WP-CLI:
```bash
cd /var/www/poradnik.pro

# Set OpenAI API key:
wp option update pearblog_openai_api_key "sk-proj-xxxxxxxxxxxx" --allow-root

# Set industry:
wp option update pearblog_industry "poradniki" --allow-root

# Set content tone:
wp option update pearblog_tone "praktyczny, pomocny, przystępny" --allow-root

# Set publish rate:
wp option update pearblog_publish_rate "1" --allow-root

# Set language:
wp option update pearblog_language "pl" --allow-root

# Enable autonomous mode:
wp option update pearblog_autonomous_mode "1" --allow-root

# Enable AI images:
wp option update pearblog_ai_images_enabled "1" --allow-root
```

### Add Initial Topics to Queue
```bash
cd /var/www/poradnik.pro

# Add topics via WP-CLI:
wp pearblog queue add "Jak skutecznie oszczędzać energię w domu" --allow-root
wp pearblog queue add "10 sposobów na lepszy sen" --allow-root
wp pearblog queue add "Jak zaplanować domowy budżet" --allow-root
wp pearblog queue add "Najlepsze aplikacje do nauki języków" --allow-root
wp pearblog queue add "Jak zorganizować małą przestrzeń" --allow-root
wp pearblog queue add "Poradnik wyboru laptopa 2026" --allow-root
wp pearblog queue add "Jak dbać o rośliny domowe" --allow-root
wp pearblog queue add "Szybkie porady kulinarne dla zapracowanych" --allow-root
wp pearblog queue add "Jak zmniejszyć stres w codziennym życiu" --allow-root
wp pearblog queue add "Podstawy zdrowego żywienia" --allow-root

# Check queue:
wp pearblog queue --list --allow-root
```

---

## 6. SSL Setup

### Install Certbot
```bash
# Install Certbot:
apt install -y certbot python3-certbot-apache  # For Apache
# or
apt install -y certbot python3-certbot-nginx   # For Nginx
```

### Obtain SSL Certificate
```bash
# For Apache:
certbot --apache -d poradnik.pro -d www.poradnik.pro

# For Nginx:
certbot --nginx -d poradnik.pro -d www.poradnik.pro

# Follow prompts:
# - Enter email: admin@poradnik.pro
# - Agree to terms: Yes
# - Redirect HTTP to HTTPS: Yes (option 2)
```

### Update WordPress URL
```bash
cd /var/www/poradnik.pro

# Update site URLs to HTTPS:
wp option update home "https://poradnik.pro" --allow-root
wp option update siteurl "https://poradnik.pro" --allow-root
```

### Test Auto-Renewal
```bash
# Test renewal:
certbot renew --dry-run

# Should succeed without errors
```

---

## 7. Testing & Verification

### Test Pipeline
```bash
cd /var/www/poradnik.pro

# Generate first article:
wp pearblog generate --allow-root

# Check statistics:
wp pearblog stats --allow-root

# Check WP-Cron:
wp cron event list --allow-root | grep pearblog
```

### Test REST API
```bash
# Health check:
curl https://poradnik.pro/wp-json/pearblog/v1/health

# Expected response:
# {"status":"ok","timestamp":1714684800}
```

### Test Frontend
```bash
# Check homepage loads:
curl -I https://poradnik.pro

# Should return HTTP 200 OK
```

### Verify Published Content
```bash
# List posts:
wp post list --post_type=post --allow-root

# Visit site:
# https://poradnik.pro
```

---

## 8. Go Live

### Enable Autonomous Mode
```bash
cd /var/www/poradnik.pro

# Enable autonomous mode:
wp option update pearblog_autonomous_mode "1" --allow-root

# Start Autopilot (26 enterprise tasks):
wp pearblog autopilot start --allow-root

# Check autopilot status:
wp pearblog autopilot status --allow-root
```

### Configure Monitoring
```bash
# Set up alerts (via WP Admin or CLI):
wp option update pearblog_alert_email "admin@poradnik.pro" --allow-root

# Optional - Slack webhook:
wp option update pearblog_alert_slack_webhook "https://hooks.slack.com/services/..." --allow-root
```

### Verify Cron Scheduling
```bash
# Check that hourly pipeline is scheduled:
wp cron event list --allow-root | grep pearblog_content_pipeline

# Should show scheduled event
```

---

## 9. Monitoring

### Server Monitoring
```bash
# Set up external monitoring (recommended):
# - UptimeRobot: https://uptimerobot.com
# - Monitor: https://poradnik.pro
# - Monitor: https://poradnik.pro/wp-json/pearblog/v1/health
# - Interval: Every 5 minutes
```

### Log Monitoring
```bash
# Watch PearBlog logs in real-time:
tail -f /var/www/poradnik.pro/wp-content/pearblog-engine.log

# Watch Apache/Nginx error logs:
tail -f /var/log/apache2/poradnik.pro-error.log
# or
tail -f /var/log/nginx/error.log

# Check for errors:
grep ERROR /var/www/poradnik.pro/wp-content/pearblog-engine.log
```

### Performance Monitoring
```bash
# Check API costs:
wp option get pearblog_ai_cost_cents --allow-root

# Convert cents to dollars:
# Example: 5800 cents = $58.00

# Check pipeline statistics:
wp pearblog stats --allow-root
```

---

## 10. Troubleshooting

### Pipeline Not Running
```bash
# Check WP-Cron is enabled:
wp option get disable_wp_cron --allow-root
# Should return empty or 0

# Manually trigger cron:
wp cron event run pearblog_content_pipeline --allow-root

# Check for errors:
wp pearblog stats --allow-root
```

### Circuit Breaker Open
```bash
# Reset circuit breaker:
wp pearblog circuit reset --allow-root

# Check OpenAI API key is valid and has credits
```

### OpenAI API Errors
```bash
# Verify API key:
wp option get pearblog_openai_api_key --allow-root

# Check OpenAI usage:
# Visit: https://platform.openai.com/usage
```

### No Images Generated
```bash
# Enable AI images:
wp option update pearblog_ai_images_enabled "1" --allow-root

# Check DALL-E 3 is available for your API key
```

### High Memory Usage
```bash
# Increase PHP memory:
nano /etc/php/8.1/fpm/php.ini
# Set: memory_limit = 512M

systemctl restart php8.1-fpm
```

### Permissions Issues
```bash
# Fix ownership:
chown -R www-data:www-data /var/www/poradnik.pro

# Fix permissions:
find /var/www/poradnik.pro -type d -exec chmod 755 {} \;
find /var/www/poradnik.pro -type f -exec chmod 644 {} \;
chmod 600 /var/www/poradnik.pro/wp-config.php
```

---

## Quick Reference Commands

```bash
# SSH to server:
ssh root@204.48.27.118

# Check pipeline status:
cd /var/www/poradnik.pro && wp pearblog stats --allow-root

# Add topic to queue:
wp pearblog queue add "Topic title" --allow-root

# Generate article now:
wp pearblog generate --allow-root

# Check health:
curl https://poradnik.pro/wp-json/pearblog/v1/health

# View logs:
tail -f /var/www/poradnik.pro/wp-content/pearblog-engine.log

# Reset circuit breaker:
wp pearblog circuit reset --allow-root

# Autopilot commands:
wp pearblog autopilot start --allow-root
wp pearblog autopilot status --allow-root
wp pearblog autopilot next --allow-root
```

---

## Success Criteria

✅ **Deployment is successful when:**

1. Site loads at https://poradnik.pro
2. SSL certificate is valid and auto-renews
3. First article generated and published automatically
4. WP-Cron running hourly pipeline
5. Health endpoint returns `{"status":"ok"}`
6. No critical errors in logs
7. Monitoring alerts configured
8. Backups scheduled

---

## Support Resources

- **Full Documentation:** See `/DOCUMENTATION-INDEX.md` in repository
- **Troubleshooting Guide:** `/TROUBLESHOOTING.md`
- **API Documentation:** `/API-DOCUMENTATION.md`
- **Disaster Recovery:** `/DISASTER-RECOVERY.md`

---

**Deployment Date:** 2026-05-02
**Version:** PearBlog Engine v6.0
**Domain:** poradnik.pro (204.48.27.118)
