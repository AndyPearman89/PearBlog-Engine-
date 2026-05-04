# ⚡ Quick Start: pt24.pro

**Domain:** pt24.pro (www.pt24.pro)
**Engine:** PearBlog v7.0 + PT24 Platform
**Purpose:** Local services directory platform

---

## 🚀 One-Line Deploy

```bash
# SSH to your server, then run:
curl -sL https://raw.githubusercontent.com/AndyPearman89/PearBlog-Engine-/main/scripts/deploy-pt24-pro.sh | bash
```

---

## 📋 Pre-Requirements

Before running the deployment:

✅ **Server Requirements:**
- Ubuntu 20.04+ or Debian 11+
- Root or sudo access
- PHP 8.1+
- MySQL 5.7+ or MariaDB 10.3+
- Apache 2.4+ or Nginx 1.18+
- At least 2GB RAM
- 20GB disk space

✅ **DNS Configuration:**
- Point `pt24.pro` → Your server IP
- Point `www.pt24.pro` → Your server IP
- Wait for DNS propagation (can take up to 24 hours)

✅ **API Keys Ready (Optional):**
- OpenAI API key (optional, for AI content generation)
- Anthropic API key (optional, for Claude)
- Google API key (optional, for Gemini)

---

## 🎯 Manual Quick Setup (5 Minutes)

### 1. Install WordPress

```bash
# Download WordPress:
cd /var/www
wget https://wordpress.org/latest.tar.gz
tar -xzf latest.tar.gz
mv wordpress pt24.pro

# Set permissions:
chown -R www-data:www-data pt24.pro
```

### 2. Configure Database

```bash
# Create database:
mysql -u root -p <<EOF
CREATE DATABASE pt24_db;
CREATE USER 'pt24_user'@'localhost' IDENTIFIED BY 'YOUR_PASSWORD';
GRANT ALL ON pt24_db.* TO 'pt24_user'@'localhost';
FLUSH PRIVILEGES;
EOF
```

### 3. Install PearBlog Engine

```bash
cd /var/www/pt24.pro/wp-content/mu-plugins

# Download v7.0.0:
wget https://github.com/AndyPearman89/PearBlog-Engine-/archive/refs/tags/v7.0.0.tar.gz
tar -xzf v7.0.0.tar.gz
mv PearBlog-Engine--7.0.0/mu-plugins/pearblog-engine ./

# Install dependencies:
cd pearblog-engine
composer install --no-dev

# Install theme:
cp -r theme/pearblog-theme /var/www/pt24.pro/wp-content/themes/
```

### 4. Configure WordPress

```bash
cd /var/www/pt24.pro

# Create config:
wp config create \
  --dbname=pt24_db \
  --dbuser=pt24_user \
  --dbpass=YOUR_PASSWORD \
  --allow-root

# Install WP:
wp core install \
  --url=https://pt24.pro \
  --title="PT24" \
  --admin_user=admin \
  --admin_email=admin@pt24.pro \
  --allow-root

# Activate theme:
wp theme activate pearblog-theme --allow-root
```

### 5. Configure PearBlog

```bash
# Add API keys to wp-config.php:
echo "define('PEARBLOG_OPENAI_API_KEY', 'sk-YOUR_KEY');" >> wp-config.php

# Set site profile:
wp option update pearblog_industry 'local_services' --allow-root
wp option update pearblog_language 'pl' --allow-root
wp option update pearblog_homepage_version 'v7' --allow-root

# Generate API key:
wp option update pearblog_api_key "$(openssl rand -hex 32)" --allow-root
```

### 6. Initialize PT24 Platform

```bash
# Initialize PT24 data (REQUIRED):
wp pt24 init --allow-root

# This creates:
# - Service categories (mechanik, hydraulik, elektryk, laweta, wulkanizacja)
# - Top 20 cities (Warszawa, Kraków, Wrocław, etc.)
# - Database tables for leads, stats, subscriptions
# - Custom rewrite rules

# Generate landing pages (service + city combinations):
wp pt24 generate-pages --batch=10 --allow-root

# Create homepage:
# In WP Admin: Pages → Add New
# Template: PT24.PRO - Homepage
# Publish

# Check platform status:
wp pt24 stats --allow-root
```

---

## ✅ Verification

### Check PT24 Platform

```bash
# Test PT24 API:
curl https://pt24.pro/wp-json/pt24/v1/businesses

# Should return business listings

# Check platform health:
curl https://pt24.pro/wp-json/pearblog/v1/health
```

### Access Admin

```
URL: https://pt24.pro/wp-admin
Login: admin / [your password]

Check: PT24 Landing Pages (in sidebar)
```

### Verify Landing Pages

```bash
# Check platform stats:
wp pt24 stats --allow-root

# List landing pages:
wp post list --post_type=pt24_landing --allow-root

# Test URL structure:
curl https://pt24.pro/mechanik/warszawa/
```

---

## 🔧 Essential PT24 WP-CLI Commands

```bash
# Platform initialization:
wp pt24 init                      # Initialize platform data
wp pt24 stats                     # View platform statistics

# Landing page generation:
wp pt24 generate-pages            # Generate all combinations
wp pt24 generate-pages --service=mechanik --city=warszawa
wp pt24 generate-pages --batch=10 # Generate in batches

# Business management:
# Add businesses via WP Admin → PT24 Businesses

# Lead management:
# View leads in database:
# SELECT * FROM wp_pt24_leads ORDER BY created_at DESC;

# API testing:
# List businesses:
curl https://pt24.pro/wp-json/pt24/v1/businesses

# Get business by ID:
curl https://pt24.pro/wp-json/pt24/v1/businesses/123

# Business stats:
curl https://pt24.pro/wp-json/pt24/v1/stats/123
```

---

## 🎨 Create Homepage

The PT24 platform uses a custom homepage template:

```bash
# In WP Admin:
# 1. Go to Pages → Add New
# 2. Title: "Strona główna" or "Homepage"
# 3. Template: Select "PT24.PRO - Homepage"
# 4. Publish
# 5. Settings → Reading → Set as homepage

# Or via WP-CLI:
wp post create \
  --post_type=page \
  --post_title="Homepage" \
  --post_status=publish \
  --page_template=page-pt24-home.php \
  --allow-root

# Set as front page:
wp option update show_on_front 'page' --allow-root
wp option update page_on_front [PAGE_ID] --allow-root
```

---

## 📊 Configure Monitoring

### Set Up Cron

```bash
# Add to crontab:
crontab -e

# Add this line:
0 * * * * cd /var/www/pt24.pro && /usr/local/bin/wp cron event run --due-now --allow-root
```

### Enable Logging

```bash
# Add to wp-config.php:
cat >> /var/www/pt24.pro/wp-config.php <<'EOF'
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
EOF
```

### Monitor Logs

```bash
# WordPress errors:
tail -f /var/www/pt24.pro/wp-content/debug.log

# PearBlog Engine:
tail -f /var/www/pt24.pro/wp-content/pearblog-engine.log
```

---

## 🔐 Security Checklist

```bash
# Change WordPress salts:
wp config shuffle-salts --allow-root

# Disable file editing:
echo "define('DISALLOW_FILE_EDIT', true);" >> wp-config.php

# Update all:
wp core update --allow-root
wp plugin update --all --allow-root
wp theme update --all --allow-root

# Install security plugin:
wp plugin install wordfence --activate --allow-root
```

---

## 🚨 Troubleshooting

### Site not loading?
```bash
# Check web server:
systemctl status apache2  # or nginx

# Check PHP:
php -v

# Test WordPress:
cd /var/www/pt24.pro && wp core verify-checksums --allow-root
```

### Landing pages not working?
```bash
# Flush rewrite rules:
wp rewrite flush --allow-root

# Re-initialize PT24:
wp pt24 init --allow-root

# Check if landing pages exist:
wp post list --post_type=pt24_landing --allow-root

# Test URL:
curl -I https://pt24.pro/mechanik/warszawa/
```

### Database errors?
```bash
# Repair database:
wp db repair --allow-root

# Optimize:
wp db optimize --allow-root
```

---

## 📞 Support

**Documentation:**
- Full Guide: [DEPLOYMENT-pt24-pro.md](DEPLOYMENT-pt24-pro.md)
- Main Docs: https://github.com/AndyPearman89/PearBlog-Engine-/

**Issues:**
- GitHub: https://github.com/AndyPearman89/PearBlog-Engine-/issues

**Community:**
- Discussions: https://github.com/AndyPearman89/PearBlog-Engine-/discussions

---

## 📈 Next Steps

After successful deployment:

1. **Add Businesses**
   - Go to WP Admin → PT24 Businesses → Add New
   - Fill in: name, phone, email, services, cities
   - Add descriptions and specializations
   - Set subscription plan (Free, PRO, Premium)

2. **Generate Landing Pages**
   - Run: `wp pt24 generate-pages --allow-root`
   - This creates service + city combinations
   - Example: /mechanik/warszawa/, /hydraulik/krakow/

3. **Customize Branding**
   - Update site title: "PT24 - Fachowcy w Twojej Okolicy"
   - Add logo in WordPress Customizer
   - Customize colors in PT24 CSS files

4. **Set Up Analytics**
   - Connect Google Analytics 4
   - Track phone clicks via pt24-cta-tracking.js
   - Monitor conversions (leads, business views)
   - Check business stats via API

5. **Enable Lead Notifications**
   - Configure email in pt24-form-handler.php:76
   - Test lead submission via frontend forms
   - Check leads in database: wp_pt24_leads

6. **Scale Up**
   - Add more cities and services
   - Onboard more businesses
   - Generate more landing pages
   - Monitor performance via `wp pt24 stats`

---

**Quick Start Version:** 1.0
**Last Updated:** May 3, 2026
**Status:** Production Ready 🚀
