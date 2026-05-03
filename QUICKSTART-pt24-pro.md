# ⚡ Quick Start: pt24.pro

**Domain:** pt24.pro (www.pt24.pro)
**Engine:** PearBlog v7.0
**Purpose:** AI-powered news & content platform

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

✅ **API Keys Ready:**
- OpenAI API key (for GPT-4o)
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
wp option update pearblog_industry 'news' --allow-root
wp option update pearblog_language 'pl' --allow-root
wp option update pearblog_publish_rate 2 --allow-root
wp option update pearblog_homepage_version 'v7' --allow-root

# Generate API key:
wp option update pearblog_api_key "$(openssl rand -hex 32)" --allow-root
```

### 6. Start Content Generation

```bash
# Add topics:
wp pearblog queue add "Wiadomości technologiczne" --allow-root
wp pearblog queue add "Biznes i finanse" --allow-root

# Generate first article:
wp pearblog generate --allow-root

# Start autopilot:
wp pearblog autopilot start --allow-root
```

---

## ✅ Verification

### Check Installation

```bash
# Test health endpoint:
curl https://pt24.pro/wp-json/pearblog/v1/health

# Should return:
# {"status":"ok","timestamp":"...","checks":{...}}
```

### Access Admin

```
URL: https://pt24.pro/wp-admin
Login: admin / [your password]

Check: PearBlog Engine → Dashboard
```

### Verify Content Generation

```bash
# Check stats:
wp pearblog stats --allow-root

# List posts:
wp post list --allow-root

# Check queue:
wp pearblog queue list --allow-root
```

---

## 🔧 Essential WP-CLI Commands

```bash
# Content generation:
wp pearblog generate              # Generate one article
wp pearblog queue add "Topic"     # Add topic to queue
wp pearblog queue list            # View queue

# Autopilot control:
wp pearblog autopilot start       # Start autonomous mode
wp pearblog autopilot status      # Check status
wp pearblog autopilot pause       # Pause generation

# Statistics:
wp pearblog stats                 # View pipeline stats
wp pearblog quality --post_id=123 # Check article quality

# System management:
wp pearblog circuit status        # Check circuit breaker
wp pearblog circuit reset         # Reset if needed
```

---

## 🎨 Enable v7 Dark UI Kit

The v7 Dark UI Kit provides a modern, dark-themed interface:

```bash
# Enable v7 UI:
wp option update pearblog_homepage_version 'v7' --allow-root

# Clear cache:
wp cache flush --allow-root

# Verify at:
# https://pt24.pro (frontend should show v7 design)
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

### Content not generating?
```bash
# Check API key:
wp option get pearblog_openai_api_key --allow-root

# Test generation:
wp pearblog generate --allow-root

# Check circuit breaker:
wp pearblog circuit status --allow-root
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

1. **Customize Branding**
   - Update site title and tagline
   - Add logo in WordPress Customizer
   - Configure colors (PearBlog Engine → Settings)

2. **Configure Monetization**
   - Add AdSense Publisher ID
   - Configure affiliate settings (Booking, Airbnb)
   - Set up revenue tracking

3. **Optimize Content Strategy**
   - Define topic categories
   - Set content tone and style
   - Configure publishing schedule

4. **Set Up Analytics**
   - Connect Google Analytics 4
   - Configure conversion tracking
   - Enable heatmap tools

5. **Scale Up**
   - Increase publish rate gradually
   - Add more topics to queue
   - Monitor performance metrics

---

**Quick Start Version:** 1.0
**Last Updated:** May 3, 2026
**Status:** Production Ready 🚀
