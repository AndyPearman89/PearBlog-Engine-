# Unified Deployment Guide: PT24.PRO & Poradnik.PRO V4

**Version**: 4.0.0
**Date**: 2026-05-04
**Platforms**: pt24.pro (Local Services) | poradnik.pro (Content Hub)

## Overview

This guide provides step-by-step instructions for deploying both PT24.PRO and Poradnik.PRO platforms with their new V4 homepage templates featuring purple gradient design and optimized conversion flows.

---

## 🎯 Quick Links

- [PT24.PRO Deployment](#pt24pro-deployment)
- [Poradnik.PRO Deployment](#poradnikpro-deployment)
- [Post-Deployment Configuration](#post-deployment-configuration)
- [Troubleshooting](#troubleshooting)

---

## 📋 Prerequisites

### Required for Both Platforms

- **Server**: Ubuntu 20.04+ or Debian 11+
- **Access**: Root or sudo privileges
- **DNS**: Configured and pointing to server
- **Tools**: WP-CLI installed and configured
- **WordPress**: 6.0+ installed
- **PHP**: 8.0+ with required extensions
- **MySQL/MariaDB**: 8.0+/10.5+
- **Nginx/Apache**: Web server configured
- **SSL**: Certificate installed (Let's Encrypt recommended)

### Repository Access

```bash
# Clone the repository
git clone https://github.com/AndyPearman89/PearBlog-Engine-.git
cd PearBlog-Engine-
```

---

## 🚀 PT24.PRO Deployment

PT24.PRO is a local services directory platform connecting users with service providers.

### Template: PT24 Homepage V4

**Features:**
- Purple gradient hero (`#667eea` → `#764ba2`)
- 6 conversion-optimized sections
- Search bar integration
- Services grid (6 categories)
- Popular cities navigation
- Business CTA for service providers

### Deployment Options

#### Option 1: Automated Deployment (Recommended)

```bash
# Full platform deployment
cd /path/to/PearBlog-Engine-
sudo ./scripts/deploy-pt24-pro.sh
```

**What it does:**
- Installs WordPress and dependencies
- Configures database
- Installs PearBlog Engine plugin
- Sets up PT24 theme
- Configures permalinks
- Initializes PT24 data structures

#### Option 2: Homepage V4 Only

If WordPress is already installed and you only want to deploy the V4 homepage:

```bash
# Navigate to WordPress directory
cd /var/www/pt24.pro

# Run V4 homepage deployment
./scripts/deploy-pt24-home-v4.sh
```

**What it does:**
1. Pre-flight checks (WP-CLI, WordPress, files)
2. Backs up current homepage settings
3. Creates page with `page-pt24-home-v4.php` template
4. Configures WordPress homepage options
5. Verifies successful deployment

#### Option 3: Manual WP-CLI Setup

```bash
# Create homepage page
PAGE_ID=$(wp post create \
  --post_type=page \
  --post_title="PT24 Homepage V4" \
  --post_status=publish \
  --page_template=page-pt24-home-v4.php \
  --porcelain)

# Set as homepage
wp option update show_on_front page
wp option update page_on_front $PAGE_ID

# Verify
wp option get page_on_front
wp option get show_on_front
```

### PT24 Post-Deployment

```bash
# Initialize PT24 data
wp pt24 init

# Generate landing pages (optional)
wp pt24 generate-pages --batch=100

# Check statistics
wp pt24 stats

# Flush rewrite rules
wp pt24 flush-rewrites
```

### File Locations (PT24)

```
theme/pearblog-theme/
├── page-pt24-home-v4.php          # V4 Homepage template
├── page-pt24-home.php              # Legacy homepage
├── page-pt24-landing.php           # Landing page template
└── assets/css/
    ├── pt24-home-v4.css            # V4 Homepage styles
    └── pt24-landing.css            # Landing styles

docs/
└── PT24-HOMEPAGE-V4-QUICKSTART.md  # V4 Quick guide

scripts/
├── deploy-pt24-pro.sh              # Full platform deployment
└── deploy-pt24-home-v4.sh          # V4 homepage only
```

---

## 📚 Poradnik.PRO Deployment

Poradnik.PRO is a content hub providing guides, advice, and connecting users with PT24 services.

### Template: Poradnik V4 HI-PRO Content Hub

**Features:**
- 10-section conversion-optimized design
- Purple gradient hero
- Quick Answer Hub
- Category Grid (6 categories)
- Featured Articles
- Cost Hub with pricing info
- Problem → Solution flow
- Internal PT24 linking
- Trust signals
- SEO-optimized footer

### Deployment Options

#### Option 1: Automated Full Deployment

```bash
# Full platform deployment
cd /path/to/PearBlog-Engine-
sudo ./scripts/deploy-poradnik-pro.sh
```

**What it does:**
- Installs WordPress and dependencies
- Configures database for poradnik.pro
- Installs PearBlog Engine plugin
- Sets up Poradnik theme
- Configures content structure
- Sets up categories and taxonomy

#### Option 2: V4 HI-PRO Homepage Manual Setup

```bash
# Navigate to WordPress directory
cd /var/www/poradnik.pro

# Create V4 HI-PRO homepage
PAGE_ID=$(wp post create \
  --post_type=page \
  --post_title="Poradnik V4 HI-PRO Content Hub" \
  --post_status=publish \
  --page_template=page-poradnik-v4-hipro.php \
  --porcelain)

# Set as homepage
wp option update show_on_front page
wp option update page_on_front $PAGE_ID

# Configure hero options (optional)
wp option update poradnik_hero_v4_title "Sprawdzone poradniki, które rozwiązują realne problemy"
wp option update poradnik_hero_v4_subtitle "Dowiedz się, co zrobić, ile to kosztuje i kiedy warto skorzystać z fachowca."

# Verify
wp post get $PAGE_ID
```

### Poradnik Post-Deployment

```bash
# Set homepage version
wp option update pearblog_homepage_version v4

# Configure quick actions (optional)
wp option update poradnik_quick_actions '[
  {"label": "Remont domu", "url": "/remont-domu/"},
  {"label": "Kredyt hipoteczny", "url": "/kredyt-hipoteczny/"},
  {"label": "Ubezpieczenie", "url": "/ubezpieczenie/"},
  {"label": "Firma sprzątająca", "url": "/sprzatanie/"}
]' --format=json

# Flush rewrite rules
wp rewrite flush
```

### File Locations (Poradnik)

```
theme/pearblog-theme/
├── page-poradnik-v4-hipro.php      # V4 HI-PRO template (10 sections)
├── page-poradnik-v4-home.php       # V4 Standard homepage
├── page-poradnik-v4-article.php    # V4 Article template
└── assets/css/
    ├── poradnik-v4-hipro.css       # V4 HI-PRO styles
    ├── poradnik-v4.css             # V4 Standard styles
    └── poradnik-landing-v5.css     # Landing V5 styles

docs/
├── PORADNIK-V4-HIPRO-CONTENT-HUB.md    # Full V4 HI-PRO docs
├── PORADNIK-V4-HIPRO-QUICKSTART.md     # Quick guide
└── PORADNIK-V4.md                      # V4 Standard docs

scripts/
└── deploy-poradnik-pro.sh              # Full deployment
```

---

## ⚙️ Post-Deployment Configuration

### Both Platforms

#### 1. Configure AdSense (Optional)

```bash
# Set publisher ID
wp option update pearblog_adsense_publisher_id "ca-pub-XXXXXXXXXX"

# Enable AdSense
wp option update pearblog_adsense_enabled 1

# Set strategy
wp option update pearblog_adsense_strategy funnel_aware

# Configure funnel stages
wp option update pearblog_adsense_enable_tofu 1
wp option update pearblog_adsense_enable_mofu 1
wp option update pearblog_adsense_enable_bofu 0
```

#### 2. SEO Configuration

```bash
# Set site title and tagline
wp option update blogname "Your Site Name"
wp option update blogdescription "Your Site Description"

# Configure permalinks
wp rewrite structure '/%postname%/'
wp rewrite flush
```

#### 3. Security Hardening

```bash
# Disable XML-RPC
wp option update xmlrpc_enabled 0

# Limit login attempts (if plugin installed)
wp plugin install limit-login-attempts-reloaded --activate

# Update salts
wp config shuffle-salts
```

### PT24 Specific

```bash
# Configure PT24 settings
wp option update pt24_enable_search 1
wp option update pt24_cities_display_limit 12
wp option update pt24_services_per_page 6

# Generate initial landing pages
wp pt24 generate-pages --batch=50
```

### Poradnik Specific

```bash
# Configure content settings
wp option update posts_per_page 10
wp option update posts_per_rss 20

# Enable Poradnik clean content system
wp option update poradnik_clean_content_enabled 1
```

---

## 🔄 Rollback Procedures

### PT24 Homepage V4 Rollback

```bash
# Restore from backup
wp option update show_on_front $(cat /tmp/pt24-v4-backup-show_on_front.txt)
wp option update page_on_front $(cat /tmp/pt24-v4-backup-page_on_front.txt)
```

### General Rollback

```bash
# Backup current state
wp db export backup-$(date +%Y%m%d-%H%M%S).sql

# Restore from specific backup
wp db import backup-YYYYMMDD-HHMMSS.sql

# Revert to previous template
wp post update PAGE_ID --page_template=OLD_TEMPLATE.php
```

---

## 🧪 Testing & Verification

### PT24.PRO Tests

```bash
# Check homepage
curl -I https://pt24.pro
curl https://pt24.pro | grep "PT24 Homepage V4"

# Test WP-CLI commands
wp pt24 stats
wp pt24 list --format=count

# Verify template
wp post get $(wp option get page_on_front) --field=page_template
```

### Poradnik.PRO Tests

```bash
# Check homepage
curl -I https://poradnik.pro
curl https://poradnik.pro | grep "Poradnik V4 HI-PRO"

# Verify template
wp post get $(wp option get page_on_front) --field=page_template

# Test content structure
wp post list --post_type=post --format=count
wp term list category --format=count
```

### Performance Tests

```bash
# Install and run performance tests
wp plugin install query-monitor --activate

# Check page load time
time curl -s -o /dev/null -w '%{time_total}' https://pt24.pro
time curl -s -o /dev/null -w '%{time_total}' https://poradnik.pro

# Run Lighthouse (requires npm)
npx lighthouse https://pt24.pro --output=html --output-path=pt24-lighthouse.html
npx lighthouse https://poradnik.pro --output=html --output-path=poradnik-lighthouse.html
```

---

## 🐛 Troubleshooting

### Common Issues

#### Issue: "Template file not found"

**Solution:**
```bash
# Verify theme is active
wp theme list

# Check file exists
ls -la wp-content/themes/pearblog-theme/page-*-v4*.php

# Activate theme if needed
wp theme activate pearblog-theme
```

#### Issue: "WP-CLI not found"

**Solution:**
```bash
# Install WP-CLI
curl -O https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar
chmod +x wp-cli.phar
sudo mv wp-cli.phar /usr/local/bin/wp

# Verify installation
wp --info
```

#### Issue: "Permission denied"

**Solution:**
```bash
# Fix WordPress file permissions
sudo chown -R www-data:www-data /var/www/*/
sudo find /var/www/*/ -type d -exec chmod 755 {} \;
sudo find /var/www/*/ -type f -exec chmod 644 {} \;
```

#### Issue: "Database connection error"

**Solution:**
```bash
# Test database connection
wp db check

# Reset database password
mysql -u root -p
ALTER USER 'db_user'@'localhost' IDENTIFIED BY 'new_password';
FLUSH PRIVILEGES;

# Update wp-config.php
wp config set DB_PASSWORD 'new_password'
```

#### Issue: "CSS/JS not loading"

**Solution:**
```bash
# Regenerate permalinks
wp rewrite flush

# Clear cache (if using cache plugin)
wp cache flush

# Check file permissions
ls -la wp-content/themes/pearblog-theme/assets/css/

# Force hard refresh in browser
# Ctrl+F5 (Windows/Linux) or Cmd+Shift+R (Mac)
```

---

## 📊 Monitoring & Maintenance

### Daily Checks

```bash
# Check site status
wp core verify-checksums
wp plugin verify-checksums --all

# Database optimization
wp db optimize

# Check for updates
wp core check-update
wp plugin list --update=available
```

### Weekly Tasks

```bash
# Backup database
wp db export backups/weekly-$(date +%Y%m%d).sql

# Update plugins (test environment first!)
wp plugin update --all --dry-run

# Check error logs
tail -n 100 /var/log/nginx/error.log
tail -n 100 wp-content/debug.log
```

### Monthly Tasks

```bash
# Full backup
tar -czf backup-$(date +%Y%m%d).tar.gz /var/www/*/

# Security scan
wp plugin install wordfence --activate
wp wordfence scan

# Performance audit
wp plugin install query-monitor --activate
```

---

## 🔗 Related Documentation

- [PT24 Homepage V4 Quickstart](PT24-HOMEPAGE-V4-QUICKSTART.md)
- [Poradnik V4 HI-PRO Content Hub](PORADNIK-V4-HIPRO-CONTENT-HUB.md)
- [Poradnik V4 HI-PRO Quickstart](PORADNIK-V4-HIPRO-QUICKSTART.md)
- [SEO V3 CLI Commands](SEO-V3-CLI-COMMANDS.md)
- [AdSense Strategy V2](../mu-plugins/pearblog-engine/docs/ADSENSE-STRATEGY-V2.md)

---

## 📞 Support

### Issue Reporting

- **GitHub Issues**: https://github.com/AndyPearman89/PearBlog-Engine-/issues
- **Repository**: https://github.com/AndyPearman89/PearBlog-Engine-

### Emergency Rollback

If something goes critically wrong:

```bash
# Stop web server
sudo systemctl stop nginx  # or apache2

# Restore from latest backup
wp db import /path/to/latest-backup.sql

# Revert to stable version
git checkout v6.0.0  # or last stable tag

# Restart web server
sudo systemctl start nginx
```

---

## 📝 Changelog

### v4.0.0 (2026-05-04)
- ✨ Added PT24 Homepage V4 with purple gradient design
- ✨ Added Poradnik V4 HI-PRO Content Hub (10 sections)
- 🚀 Automated deployment scripts for both platforms
- 📚 Comprehensive documentation and quickstart guides
- 🔧 WP-CLI commands for easy setup
- 🔄 Rollback support with backup functionality

### v3.0.0
- Legacy V3 templates
- Basic deployment scripts

---

## ✅ Deployment Checklist

### PT24.PRO

- [ ] Server prepared with prerequisites
- [ ] DNS configured and propagated
- [ ] SSL certificate installed
- [ ] WordPress installed
- [ ] WP-CLI configured
- [ ] Run deployment script
- [ ] Verify homepage V4 template
- [ ] Test services navigation
- [ ] Test cities grid
- [ ] Configure AdSense (optional)
- [ ] Run performance tests
- [ ] Set up monitoring
- [ ] Create backup schedule

### Poradnik.PRO

- [ ] Server prepared with prerequisites
- [ ] DNS configured and propagated
- [ ] SSL certificate installed
- [ ] WordPress installed
- [ ] WP-CLI configured
- [ ] Run deployment script
- [ ] Verify V4 HI-PRO template
- [ ] Test all 10 sections
- [ ] Configure content categories
- [ ] Set up PT24 linking
- [ ] Configure AdSense (optional)
- [ ] Run performance tests
- [ ] Set up monitoring
- [ ] Create backup schedule

---

**End of Deployment Guide**

For questions or issues, please refer to the troubleshooting section or create an issue on GitHub.
