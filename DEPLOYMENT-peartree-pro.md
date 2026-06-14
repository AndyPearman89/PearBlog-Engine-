# 🚀 Deployment Guide: peartree.pro (WordPress Multisite)

**Domain:** peartree.pro
**Network Type:** WordPress Multisite (Subdomain)
**Server:** TBD - Update with actual server IP
**User:** root
**Target:** Production deployment of PearBlog Engine v6.0 on Multisite
**Language:** English

---

## Quick Deploy Commands

```bash
# From your local machine:
ssh root@YOUR_SERVER_IP

# Once connected, run:
curl -sL https://raw.githubusercontent.com/AndyPearman89/PearBlog-Engine-/main/scripts/deploy-peartree-pro.sh | bash
```

**⚠️ Note:** Before running the script, update `SERVER_IP` in `deploy-peartree-pro.sh` with your actual server IP address.

---

## Table of Contents

1. [Prerequisites Check](#1-prerequisites-check)
2. [Wildcard DNS Setup](#2-wildcard-dns-setup)
3. [Initial Server Setup](#3-initial-server-setup)
4. [WordPress Installation](#4-wordpress-installation)
5. [Multisite Configuration](#5-multisite-configuration)
6. [Web Server Configuration for Multisite](#6-web-server-configuration-for-multisite)
7. [PearBlog Engine Deployment](#7-pearblog-engine-deployment)
8. [Network Plugin Activation](#8-network-plugin-activation)
9. [Configuration](#9-configuration)
10. [Creating Subsites](#10-creating-subsites)
11. [Per-Site PearBlog Configuration](#11-per-site-pearblog-configuration)
12. [Wildcard SSL Certificate](#12-wildcard-ssl-certificate)
13. [Testing & Verification](#13-testing--verification)
14. [Go Live](#14-go-live)
15. [Monitoring](#15-monitoring)
16. [Troubleshooting](#16-troubleshooting)

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

---

## 2. Wildcard DNS Setup

WordPress Multisite with subdomain mode requires wildcard DNS so that every subsite subdomain resolves to your server.

### Required DNS Records

| Record Type | Host | Value |
|-------------|------|-------|
| A | `peartree.pro` | `YOUR_SERVER_IP` |
| A | `*.peartree.pro` | `YOUR_SERVER_IP` |

> **Note:** The `*.peartree.pro` wildcard record ensures all subsites (`blog.peartree.pro`, `news.peartree.pro`, etc.) resolve to the same server.

### DNS Verification
```bash
# Verify apex domain:
dig peartree.pro +short
# Should return: YOUR_SERVER_IP

# Verify wildcard (test a subsite):
dig blog.peartree.pro +short
# Should return: YOUR_SERVER_IP

nslookup peartree.pro
nslookup news.peartree.pro
```

> ⚠️ **Wait for DNS propagation** (up to 24–48 hours) before proceeding with SSL setup.

---

## 3. Initial Server Setup

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
mkdir -p /var/www/peartree.pro
chown www-data:www-data /var/www/peartree.pro
```

---

## 4. WordPress Installation

### Download WordPress
```bash
cd /var/www/peartree.pro
wp core download --allow-root
```

### Database Setup
```bash
# Login to MySQL:
mysql -u root -p

# Create database and user:
CREATE DATABASE peartree_pro CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'peartree_user'@'localhost' IDENTIFIED BY 'STRONG_PASSWORD_HERE';
GRANT ALL PRIVILEGES ON peartree_pro.* TO 'peartree_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

### Configure WordPress
```bash
cd /var/www/peartree.pro

# Create wp-config.php:
wp config create \
  --dbname=peartree_pro \
  --dbuser=peartree_user \
  --dbpass=YOUR_DB_PASSWORD \
  --dbhost=localhost \
  --dbcharset=utf8mb4 \
  --dbcollate=utf8mb4_unicode_ci \
  --allow-root

# Set WordPress constants:
wp config set WP_MEMORY_LIMIT "512M" --allow-root
wp config set DISABLE_WP_CRON false --raw --allow-root
wp config set WP_DEBUG false --raw --allow-root
wp config set table_prefix "pt_" --allow-root

# Allow Multisite:
wp config set WP_ALLOW_MULTISITE true --raw --allow-root
```

### Install WordPress (single-site first)
```bash
wp core install \
  --url="http://peartree.pro" \
  --title="PearTree Pro - Multi-Site Content Network" \
  --admin_user="admin" \
  --admin_password="STRONG_ADMIN_PASSWORD" \
  --admin_email="your-email@example.com" \
  --allow-root

# Set permalink structure:
wp rewrite structure '/%postname%/' --allow-root
wp rewrite flush --allow-root
```

---

## 5. Multisite Configuration

### Convert to WordPress Multisite

```bash
# Convert single-site install to subdomain multisite:
wp core multisite-convert --subdomains --allow-root
```

### wp-config.php Multisite Constants

After conversion, add all required constants using WP-CLI:

```bash
wp config set MULTISITE true --raw --allow-root
wp config set SUBDOMAIN_INSTALL true --raw --allow-root
wp config set DOMAIN_CURRENT_SITE "peartree.pro" --allow-root
wp config set PATH_CURRENT_SITE "/" --allow-root
wp config set SITE_ID_CURRENT_SITE 1 --raw --allow-root
wp config set BLOG_ID_CURRENT_SITE 1 --raw --allow-root
```

Your `wp-config.php` should contain the following block (after `/* That's all, stop editing! */`):

```php
define('WP_ALLOW_MULTISITE', true);
define('MULTISITE', true);
define('SUBDOMAIN_INSTALL', true);
define('DOMAIN_CURRENT_SITE', 'peartree.pro');
define('PATH_CURRENT_SITE', '/');
define('SITE_ID_CURRENT_SITE', 1);
define('BLOG_ID_CURRENT_SITE', 1);
```

---

## 6. Web Server Configuration for Multisite

### Apache: .htaccess for Multisite Subdomain

Create or replace `/var/www/peartree.pro/.htaccess`:

```apache
# WordPress Multisite .htaccess
RewriteEngine On
RewriteBase /
RewriteRule ^index\.php$ - [L]

# Uploaded files
RewriteRule ^([_0-9a-zA-Z-]+/)?files/(.+) wp-includes/ms-files.php?file=$2 [L]

# Add trailing slash to /wp-admin
RewriteRule ^([_0-9a-zA-Z-]+/)?wp-admin$ $1wp-admin/ [R=301,L]

RewriteCond %{REQUEST_FILENAME} -f [OR]
RewriteCond %{REQUEST_FILENAME} -d
RewriteRule ^ - [L]
RewriteRule ^([_0-9a-zA-Z-]+/)?(wp-(content|admin|includes).*) $2 [L]
RewriteRule ^([_0-9a-zA-Z-]+/)?(.*\.php)$ $2 [L]
RewriteRule . index.php [L]
```

Also ensure your Apache VirtualHost includes the wildcard `ServerAlias`:

```apache
<VirtualHost *:80>
    ServerName peartree.pro
    ServerAlias *.peartree.pro
    DocumentRoot /var/www/peartree.pro
    # ... rest of VirtualHost config
</VirtualHost>
```

### Nginx: Wildcard Subdomain Configuration

Add the following server block to your Nginx configuration (e.g., `/etc/nginx/sites-available/peartree.pro`):

```nginx
server {
    listen 80;
    server_name peartree.pro *.peartree.pro;
    root /var/www/peartree.pro;
    index index.php index.html;

    location / {
        try_files $uri $uri/ /index.php?$args;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.1-fpm.sock;
    }

    location ~* /files/(.+)$ {
        try_files /wp-content/blogs.dir/$blogid/$uri /wp-includes/ms-files.php?file=$1 last;
        access_log off;
        log_not_found off;
        expires max;
    }

    location = /favicon.ico { log_not_found off; access_log off; }
    location = /robots.txt  { log_not_found off; access_log off; allow all; }
    location ~* \.(css|gif|ico|jpeg|jpg|js|png)$ { expires max; log_not_found off; }
}
```

Enable and reload:
```bash
ln -s /etc/nginx/sites-available/peartree.pro /etc/nginx/sites-enabled/
nginx -t
systemctl reload nginx
```

---

## 7. PearBlog Engine Deployment

### Clone Repository
```bash
cd /tmp
git clone https://github.com/AndyPearman89/PearBlog-Engine-.git
cd PearBlog-Engine-
```

### Deploy MU-Plugin
```bash
# Create mu-plugins directory:
mkdir -p /var/www/peartree.pro/wp-content/mu-plugins

# Copy PearBlog Engine:
cp -r mu-plugins/pearblog-engine /var/www/peartree.pro/wp-content/mu-plugins/

# Set correct permissions:
chown -R www-data:www-data /var/www/peartree.pro/wp-content/mu-plugins/pearblog-engine
```

### Deploy Theme
```bash
# Copy theme:
cp -r theme/pearblog-theme /var/www/peartree.pro/wp-content/themes/

# Activate theme on main site:
cd /var/www/peartree.pro
wp theme activate pearblog-theme --allow-root

# Set permissions:
chown -R www-data:www-data /var/www/peartree.pro/wp-content/themes/pearblog-theme
```

---

## 8. Network Plugin Activation

In WordPress Multisite, plugins must be **network-activated** to be available across all subsites.

```bash
cd /var/www/peartree.pro

# Network-activate PearBlog Engine:
wp plugin activate pearblog-engine --network --allow-root
```

Verify activation:
```bash
wp plugin list --network --allow-root | grep pearblog
```

---

## 9. Configuration

### Configure PearBlog Engine (Network Level)
```bash
cd /var/www/peartree.pro

# Set OpenAI API key:
wp option update pearblog_openai_api_key "sk-proj-YOUR_KEY_HERE" --allow-root

# Configure network-level settings:
wp option update pearblog_industry "multi-niche content network" --allow-root
wp option update pearblog_tone "authoritative, informative, engaging for a broad English-speaking audience" --allow-root

# Set content settings:
wp option update pearblog_publish_rate "0.5" --allow-root  # 1 article every 2 hours
wp option update pearblog_language "en" --allow-root
wp option update pearblog_ai_images_enabled "1" --allow-root
```

### Add Initial Content Topics (Main Site)
```bash
cd /var/www/peartree.pro

wp pearblog queue add "Content marketing strategies for bloggers in 2026" --allow-root
wp pearblog queue add "How to start a successful blog from scratch" --allow-root
wp pearblog queue add "SEO fundamentals: ranking your content in Google" --allow-root
wp pearblog queue add "AI writing tools: best picks for content creators" --allow-root
wp pearblog queue add "WordPress tips and tricks for site owners" --allow-root
# ... (30 topics added automatically by deploy script)

# Verify queue:
wp pearblog queue list --allow-root
```

### GitHub Secrets Configuration

Go to: https://github.com/AndyPearman89/PearBlog-Engine-
Navigate to: **Settings** → **Secrets and variables** → **Actions**

Add these secrets (prefix: `PEARTREE_`):

| Secret Name | Value |
|-------------|-------|
| `PEARTREE_SSH_HOST` | YOUR_SERVER_IP |
| `PEARTREE_SSH_USER` | root |
| `PEARTREE_SSH_PRIVATE_KEY` | [Your SSH private key content] |
| `PEARTREE_WP_PATH` | /var/www/peartree.pro |
| `PEARTREE_ROOT_PASSWORD` | [MySQL root password] |
| `PEARTREE_OPENAI_API_KEY` | sk-proj-... |

---

## 10. Creating Subsites

### Create Initial Subsites

```bash
cd /var/www/peartree.pro

# Create blog subsite:
wp site create --slug="blog" --title="PearTree Blog" --email="admin@peartree.pro" --allow-root

# Create news subsite:
wp site create --slug="news" --title="PearTree News" --email="admin@peartree.pro" --allow-root

# Create reviews subsite:
wp site create --slug="reviews" --title="PearTree Reviews" --email="admin@peartree.pro" --allow-root
```

### Managing Subsites

```bash
# List all sites in the network:
wp site list --allow-root

# Create an additional subsite:
wp site create --slug="mysite" --title="My Site" --email="admin@peartree.pro" --allow-root

# Delete a subsite (use site ID from wp site list):
wp site delete <site_id> --allow-root

# Archive a subsite:
wp site archive <site_id> --allow-root
```

---

## 11. Per-Site PearBlog Configuration

Each subsite can have its own PearBlog Engine settings. Use the `--url` flag to target specific subsites.

```bash
# Configure PearBlog on blog subsite (blog_id=2):
wp --url="blog.peartree.pro" option update pearblog_industry "blogging tips" --allow-root
wp --url="blog.peartree.pro" option update pearblog_language "en" --allow-root
wp --url="blog.peartree.pro" option update pearblog_publish_rate "0.5" --allow-root

# Configure PearBlog on news subsite (blog_id=3):
wp --url="news.peartree.pro" option update pearblog_industry "digital news and media" --allow-root

# Configure PearBlog on reviews subsite (blog_id=4):
wp --url="reviews.peartree.pro" option update pearblog_industry "product reviews and recommendations" --allow-root

# Generate content on a specific subsite:
wp --url="blog.peartree.pro" pearblog generate --allow-root

# Check stats on a specific subsite:
wp --url="news.peartree.pro" pearblog stats --allow-root

# Network-wide stats loop:
wp site list --field=url --allow-root | while read url; do
  echo "=== $url ==="; wp --url="$url" pearblog stats --allow-root
done
```

---

## 12. Wildcard SSL Certificate

WordPress Multisite with subdomain mode requires a **wildcard SSL certificate** to cover all subsites.

### Install Certbot
```bash
apt install -y certbot
```

### Obtain Wildcard SSL Certificate (DNS-01 Challenge)

```bash
certbot certonly --manual --preferred-challenges dns \
  -d peartree.pro -d "*.peartree.pro" \
  --email your-email@example.com \
  --agree-tos
```

> **Note:** DNS-01 challenge requires you to add a `_acme-challenge.peartree.pro` TXT record in your DNS provider's control panel. Certbot will display the required value during the process.

### Configure Web Server with Wildcard Certificate

**Apache:**
```apache
<VirtualHost *:443>
    ServerName peartree.pro
    ServerAlias *.peartree.pro
    DocumentRoot /var/www/peartree.pro

    SSLEngine on
    SSLCertificateFile /etc/letsencrypt/live/peartree.pro/fullchain.pem
    SSLCertificateKeyFile /etc/letsencrypt/live/peartree.pro/privkey.pem
</VirtualHost>
```

**Nginx:**
```nginx
server {
    listen 443 ssl;
    server_name peartree.pro *.peartree.pro;

    ssl_certificate /etc/letsencrypt/live/peartree.pro/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/peartree.pro/privkey.pem;

    # ... rest of server block
}
```

### Update All Sites to HTTPS
```bash
cd /var/www/peartree.pro

# Update main site:
wp option update home "https://peartree.pro" --allow-root
wp option update siteurl "https://peartree.pro" --allow-root

# Update all subsites:
wp site list --field=url --allow-root | while read SITE_URL; do
  HTTPS_URL="${SITE_URL/http:\/\//https://}"
  wp --url="$SITE_URL" option update home "$HTTPS_URL" --allow-root
  wp --url="$SITE_URL" option update siteurl "$HTTPS_URL" --allow-root
done
```

### Test SSL Auto-Renewal
```bash
certbot renew --dry-run
```

---

## 13. Testing & Verification

### Test Content Generation
```bash
cd /var/www/peartree.pro

# Generate first article on main site:
wp pearblog generate --allow-root

# Generate on subsite:
wp --url="blog.peartree.pro" pearblog generate --allow-root

# Check statistics:
wp pearblog stats --allow-root

# View queue:
wp pearblog queue list --allow-root
```

### Test Health Endpoint
```bash
curl https://peartree.pro/wp-json/pearblog/v1/health
# Should return: {"status":"ok","timestamp":...}

curl https://blog.peartree.pro/wp-json/pearblog/v1/health
# Should return: {"status":"ok","timestamp":...}
```

### Verify Network
```bash
# List all network sites:
wp site list --allow-root

# Verify all sites are active:
wp site list --field=archived --allow-root

# Check autonomous mode on all sites:
wp site list --field=url --allow-root | while read url; do
  echo "$url: $(wp --url="$url" option get pearblog_autonomous_mode --allow-root)"
done
```

### Start Autopilot
```bash
# Start Enterprise Autopilot on main site:
wp pearblog autopilot start --allow-root

# Check autopilot status:
wp pearblog autopilot status --allow-root
```

---

## 14. Go Live

### Final Checks
```bash
# Verify main site:
curl -I https://peartree.pro
# Should return: HTTP/2 200

# Verify subsites:
curl -I https://blog.peartree.pro
curl -I https://news.peartree.pro
curl -I https://reviews.peartree.pro

# Check Network Admin:
# Visit: https://peartree.pro/wp-admin/network/

# Check PearBlog Engine dashboard:
# Visit: https://peartree.pro/wp-admin/admin.php?page=pearblog-engine
```

### Performance Optimization
```bash
# Enable object caching (if Redis installed):
wp plugin install redis-cache --activate --allow-root
wp redis enable --allow-root
```

---

## 15. Monitoring

### Check Logs
```bash
# PearBlog Engine logs:
tail -f /var/www/peartree.pro/wp-content/pearblog-engine.log

# WordPress debug log:
tail -f /var/www/peartree.pro/wp-content/debug.log

# Apache/Nginx error logs:
tail -f /var/log/apache2/error.log  # Apache
tail -f /var/log/nginx/error.log    # Nginx
```

### Monitor Network Sites
```bash
# List all cron events:
wp cron event list --allow-root

# Network-wide article count:
wp site list --field=url --allow-root | while read url; do
  echo "$url: $(wp --url="$url" post list --post_status=publish --post_type=post --format=count --allow-root) posts"
done
```

### Check API Costs
```bash
# View OpenAI API costs:
wp pearblog stats --allow-root

# Check per-site costs:
wp site list --field=url --allow-root | while read url; do
  echo "=== $url ==="; wp --url="$url" pearblog stats --allow-root
done
```

### Monitor Autopilot
```bash
# Check autopilot status:
wp pearblog autopilot status --allow-root

# View autopilot progress:
wp option get pearblog_autopilot_state --format=json --allow-root
```

---

## 16. Troubleshooting

### Subsites Not Accessible

**Problem:** Subdomains return 404 or don't resolve.

**Solution:**
```bash
# Check DNS:
dig blog.peartree.pro +short

# Check web server wildcard config:
# Apache: verify ServerAlias *.peartree.pro in VirtualHost
# Nginx: verify server_name peartree.pro *.peartree.pro;

# Flush permalinks:
wp rewrite flush --allow-root
```

### Content Not Generating

**Problem:** Articles are not being generated automatically.

**Solution:**
```bash
# Check autonomous mode:
wp option get pearblog_autonomous_mode --allow-root
# Should return: 1

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

### SSL Certificate Issues

**Problem:** SSL not working or expired.

**Solution:**
```bash
# Check certificate status:
certbot certificates

# Renew wildcard certificate:
certbot renew

# Test renewal process:
certbot renew --dry-run

# Verify wildcard covers subsites:
echo | openssl s_client -connect blog.peartree.pro:443 2>/dev/null | openssl x509 -noout -subject -issuer
```

### Multisite Table Errors

**Problem:** Database errors related to multisite tables.

**Solution:**
```bash
# Check multisite tables exist:
mysql -u peartree_user -p peartree_pro -e "SHOW TABLES LIKE 'pt_%';"

# Repair tables:
wp db repair --allow-root

# Check wp-config.php multisite constants:
wp config get MULTISITE --allow-root
wp config get SUBDOMAIN_INSTALL --allow-root
```

### Permission Issues

**Problem:** Files cannot be written.

**Solution:**
```bash
# Fix permissions:
chown -R www-data:www-data /var/www/peartree.pro/wp-content
chmod -R 755 /var/www/peartree.pro/wp-content

# Check current permissions:
ls -la /var/www/peartree.pro/wp-content/
```

---

## Post-Deployment Checklist

- [x] Wildcard DNS configured (`*.peartree.pro` → SERVER_IP)
- [x] Server accessible via SSH
- [x] PHP 8.1+ installed with all extensions
- [x] MySQL database created and configured
- [x] WordPress installed (single-site) and accessible
- [x] WordPress converted to Multisite (subdomain)
- [x] Multisite constants in wp-config.php
- [x] Web server wildcard subdomain configured (Apache/Nginx)
- [x] PearBlog Engine MU-plugin deployed
- [x] PearBlog Engine network-activated
- [x] Theme activated
- [x] OpenAI API key configured
- [x] Initial subsites created (blog, news, reviews)
- [x] Per-site PearBlog configuration applied
- [x] Initial topics added to main site queue
- [x] Wildcard SSL certificate installed
- [x] All subsites updated to HTTPS
- [x] Autonomous mode enabled network-wide
- [x] First article generated successfully
- [x] Health endpoint responding on main site and subsites
- [x] Cron jobs scheduled
- [x] Autopilot started
- [x] Monitoring logs checked
- [x] GitHub Secrets configured (PEARTREE_ prefix)

---

## Quick Reference Commands

```bash
# SSH to server:
ssh root@YOUR_SERVER_IP

# Navigate to WordPress:
cd /var/www/peartree.pro

# List all network sites:
wp site list --allow-root

# Generate article on main site:
wp pearblog generate --allow-root

# Generate on subsite:
wp --url="blog.peartree.pro" pearblog generate --allow-root

# Check stats:
wp pearblog stats --allow-root

# View queue:
wp pearblog queue list --allow-root

# Add topic to main site:
wp pearblog queue add "Your topic here" --allow-root

# Check logs:
tail -f wp-content/pearblog-engine.log

# Autopilot status:
wp pearblog autopilot status --allow-root

# Health check:
curl https://peartree.pro/wp-json/pearblog/v1/health

# Network-wide stats:
wp site list --field=url --allow-root | while read url; do
  echo "=== $url ==="; wp --url="$url" pearblog stats --allow-root
done
```

---

**Last Updated:** 2026-05-03
**Version:** 6.0.0
**Network Type:** WordPress Multisite (Subdomain)
**Industry:** Multi-niche content network
**Publish Rate:** 0.5/hour per site
**Language:** English (en)
