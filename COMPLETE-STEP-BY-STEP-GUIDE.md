# 🚀 PearBlog Engine - Complete Step-by-Step Guide

**Version:** v7.10.0
**Date:** 2026-05-03
**Status:** Production Ready

This is the complete operational guide for PearBlog Engine - from zero to production.

---

## 📋 Table of Contents

1. [Prerequisites & Requirements](#1-prerequisites--requirements)
2. [Initial Installation & Deployment](#2-initial-installation--deployment)
3. [First-Time Configuration](#3-first-time-configuration)
4. [Content Generation Setup](#4-content-generation-setup)
5. [PT24 Marketplace Setup](#5-pt24-marketplace-setup)
6. [Monetization Configuration](#6-monetization-configuration)
7. [SEO Optimization](#7-seo-optimization)
8. [Analytics & Monitoring](#8-analytics--monitoring)
9. [Lead Management](#9-lead-management)
10. [Multi-Site Management](#10-multi-site-management)
11. [Backup & Recovery](#11-backup--recovery)
12. [Troubleshooting](#12-troubleshooting)
13. [Advanced Operations](#13-advanced-operations)
14. [Daily Operations Checklist](#14-daily-operations-checklist)

---

## 1️⃣ Prerequisites & Requirements

### **Step 1.1: Verify Server Requirements**

**Minimum Requirements:**
- Ubuntu 20.04 LTS or newer
- PHP 8.1 or higher
- MySQL 5.7+ or MariaDB 10.3+
- 2GB RAM minimum (4GB recommended)
- 20GB disk space
- Root or sudo access

**Check your PHP version:**
```bash
php -v
# Expected output: PHP 8.1.x or higher
```

**Check MySQL:**
```bash
mysql --version
```

### **Step 1.2: Prepare API Keys**

Collect these before starting:
- [x] OpenAI API key (required for content generation)
- [x] Google AdSense Publisher ID (optional, for monetization)
- [x] SSL email address (for Let's Encrypt)
- [x] Domain name pointed to your server IP

### **Step 1.3: Server Access**

Test SSH connection:
```bash
ssh root@your-server-ip
# or
ssh your-username@your-server-ip
```

---

## 2️⃣ Initial Installation & Deployment

### **Step 2.1: Quick Deployment (Recommended)**

**One-line automated deployment:**
```bash
curl -sL https://raw.githubusercontent.com/AndyPearman89/PearBlog-Engine-/main/scripts/deploy-poradnik-pro.sh | bash
```

**What this does:**
- ✅ Installs PHP 8.1+, MySQL, Nginx
- ✅ Downloads and configures WordPress
- ✅ Installs PearBlog Engine plugin
- ✅ Configures SSL/HTTPS with Let's Encrypt
- ✅ Sets up database
- ✅ Creates admin user

**During installation, you'll be prompted for:**
1. MySQL root password (create a strong one)
2. WordPress database name (default: `wordpress`)
3. Admin email
4. Admin password
5. Site URL/domain
6. SSL email for Let's Encrypt

### **Step 2.2: Manual Installation (Alternative)**

If you prefer manual installation:

**Install dependencies:**
```bash
# Update system
sudo apt update && sudo apt upgrade -y

# Install PHP 8.1
sudo apt install -y php8.1 php8.1-fpm php8.1-mysql php8.1-xml php8.1-mbstring php8.1-curl php8.1-zip php8.1-gd

# Install MySQL
sudo apt install -y mysql-server

# Install Nginx
sudo apt install -y nginx

# Install WP-CLI
curl -O https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar
chmod +x wp-cli.phar
sudo mv wp-cli.phar /usr/local/bin/wp
```

**Download WordPress:**
```bash
cd /var/www/
sudo mkdir your-domain.com
cd your-domain.com
sudo wp core download --allow-root
```

**Create database:**
```bash
sudo mysql -u root -p
```
```sql
CREATE DATABASE wordpress;
CREATE USER 'wpuser'@'localhost' IDENTIFIED BY 'strong_password';
GRANT ALL PRIVILEGES ON wordpress.* TO 'wpuser'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

**Configure WordPress:**
```bash
sudo wp config create --dbname=wordpress --dbuser=wpuser --dbpass=strong_password --allow-root
sudo wp core install --url=https://your-domain.com --title="Your Site" --admin_user=admin --admin_password=admin_password --admin_email=you@email.com --allow-root
```

**Clone PearBlog Engine:**
```bash
cd /var/www/your-domain.com/wp-content/
sudo git clone https://github.com/AndyPearman89/PearBlog-Engine- temp-repo
sudo cp -r temp-repo/mu-plugins ./
sudo cp -r temp-repo/theme/pearblog-theme ./themes/
sudo rm -rf temp-repo
```

**Activate theme:**
```bash
sudo wp theme activate pearblog-theme --allow-root
```

### **Step 2.3: Verify Installation**

**Check WordPress is running:**
```bash
curl http://localhost
```

**Check WP-CLI:**
```bash
wp --info --allow-root
```

**Expected output:**
```
OS:     Linux
Shell:  /bin/bash
PHP binary:    /usr/bin/php8.1
PHP version:   8.1.x
```

---

## 3️⃣ First-Time Configuration

### **Step 3.1: Access WordPress Admin**

1. Open browser: `https://your-domain.com/wp-admin`
2. Login with credentials from installation
3. You should see the WordPress dashboard

### **Step 3.2: Access PearBlog Admin Panel**

**Navigate to:**
- Look for **"PearBlog v7"** or **"PearBlog v8"** in the left sidebar
- Click to open the admin panel

**First-time setup wizard will appear if enabled:**
- Follow the onboarding wizard
- Or configure manually in the next steps

### **Step 3.3: Configure Core Settings**

**Strategy Tab (AI Configuration):**

1. Click **Strategy Tab**
2. Enter your **OpenAI API Key**
3. Select AI model:
   - `gpt-4` (best quality, slower)
   - `gpt-3.5-turbo` (faster, lower cost)
4. Set temperature: `0.7` (recommended for creative content)
5. Click **Save Settings**

**Settings Tab (Site Profile):**

1. Click **Settings Tab**
2. Configure:
   ```
   Industry: [Select your niche]
   Examples: Travel, Technology, Home Services, Finance, Health

   Tone: Professional / Casual / Friendly / Authoritative

   Language: Polish / English / Other

   Publishing Rate: 1-5 articles per hour
   (Start with 1, increase as you monitor)
   ```
3. Click **Save Profile**

### **Step 3.4: Test AI Connection**

**Via WP-CLI:**
```bash
wp pearblog test-ai --allow-root
```

**Expected output:**
```
✓ OpenAI API connection successful
✓ Model: gpt-3.5-turbo available
✓ Token limit: 4096
```

**Via Admin Panel:**
1. Go to **Dashboard Tab**
2. Look for "AI Status" indicator
3. Should show green ✓ "Connected"

---

## 4️⃣ Content Generation Setup

### **Step 4.1: Add Topics to Queue**

**Method 1: Via Admin Panel**

1. Go to **Content Engine Tab**
2. Find "Topic Queue" section
3. Add topics (one per line):
   ```
   Jak wybrać najlepszy hotel w Krakowie
   Top 10 restauracji w Warszawie
   Przewodnik po Zakopanem zimą
   Najlepsze atrakcje dla dzieci w Polsce
   ```
4. Click **Add to Queue**

**Method 2: Via WP-CLI**
```bash
wp pearblog add-topic "Jak wybrać najlepszy hotel w Krakowie" --allow-root
wp pearblog add-topic "Top 10 restauracji w Warszawie" --allow-root
```

**Method 3: Bulk Import from CSV**
```bash
# Create CSV file: topics.csv
# Format: one topic per line

wp pearblog import-topics topics.csv --allow-root
```

### **Step 4.2: Configure Content Settings**

**Content Engine Tab settings:**

1. **Content Length:** 1500-3000 words (recommended)
2. **Include Images:** ✓ Yes (requires API key)
3. **Include FAQ:** ✓ Yes
4. **Include Conclusion:** ✓ Yes
5. **Internal Links:** 5-10 per article
6. **Publish Immediately:** ✓ or Draft (your choice)

### **Step 4.3: Start Autonomous Content Generation**

**Enable Autopilot Mode:**

**Via WP-CLI:**
```bash
# Start autopilot
wp pearblog autopilot start --allow-root

# Check status
wp pearblog autopilot status --allow-root

# Expected output:
# Autopilot Status: ACTIVE
# Current Phase: Phase 1 - Production Hardening
# Tasks Completed: 3/26
# Next Task: Add 5 SEO-optimized posts
```

**Via Admin Panel:**
1. Go to **Automation Tab**
2. Click **"Start Autopilot"** button
3. Select phase (or start from Phase 1)
4. Confirm

**Pause/Resume:**
```bash
wp pearblog autopilot pause --allow-root
wp pearblog autopilot resume --allow-root
```

### **Step 4.4: Manual Content Generation**

**Generate single article:**
```bash
wp pearblog generate --topic="Najlepsze restauracje w Krakowie" --allow-root
```

**Generate multiple articles:**
```bash
wp pearblog generate --count=5 --allow-root
```

### **Step 4.5: Monitor Generation Progress**

**Real-time monitoring:**
```bash
# Watch logs
tail -f /var/www/your-domain.com/wp-content/pearblog-engine.log

# Check pipeline status
wp pearblog status --allow-root
```

**Admin Panel monitoring:**
1. Go to **Dashboard Tab**
2. View "Recent Activity" widget
3. Check "Content Pipeline" status

---

## 5️⃣ PT24 Marketplace Setup

### **Step 5.1: Understand PT24 System**

PT24 is the multi-vertical marketplace system that generates:
- **Landing pages:** `/{city}/{service}` (e.g., `/krakow/mechanik`)
- **Ranking pages:** `/ranking/{city}/{service}` (e.g., `/ranking/krakow/mechanik`)

**Default services:**
- mechanik (Mechanic)
- hydraulik (Plumber)
- elektryk (Electrician)
- pompa-ciepla (Heat pump)
- remont-lazienki (Bathroom renovation)
- fotowoltaika (Photovoltaic/Solar)

**Default cities:**
- Kraków, Warszawa, Wrocław, Katowice, Poznań, Gdańsk

### **Step 5.2: Verify PT24 Module**

**Check if PT24 is enabled:**
```bash
wp plugin list --allow-root | grep pearblog
```

**Check PT24 post type:**
```bash
wp post-type list --allow-root | grep pt24
```

### **Step 5.3: Generate PT24 Landing Pages**

**Generate all pages (recommended for first run):**
```bash
# This generates 72 pages (6 services × 6 cities × 2 types)
wp pt24 generate --all --allow-root
```

**Expected output:**
```
Generating PT24 landing pages...
✓ Created: /krakow/mechanik
✓ Created: /ranking/krakow/mechanik
✓ Created: /krakow/hydraulik
✓ Created: /ranking/krakow/hydraulik
...
Total: 72 pages generated successfully
```

**Generate specific service/city:**
```bash
wp pt24 generate --service=mechanik --city=krakow --allow-root
```

**Generate single city (all services):**
```bash
wp pt24 generate --city=warszawa --allow-root
```

**Generate single service (all cities):**
```bash
wp pt24 generate --service=elektryk --allow-root
```

### **Step 5.4: Customize Services & Cities**

**Edit configuration file:**
```bash
nano /var/www/your-domain.com/wp-content/mu-plugins/pearblog-engine/src/Content/PT24LandingGenerator.php
```

**Add new service:**
```php
private static $services = [
    'mechanik' => 'Mechanik',
    'hydraulik' => 'Hydraulik',
    'elektryk' => 'Elektryk',
    'lakiernik' => 'Lakiernik', // NEW
];
```

**Add new city:**
```php
private static $cities = [
    'krakow' => 'Kraków',
    'warszawa' => 'Warszawa',
    'lodz' => 'Łódź', // NEW
];
```

**Regenerate after changes:**
```bash
wp pt24 generate --all --allow-root
```

### **Step 5.5: Test PT24 Pages**

**Visit pages in browser:**
1. `https://your-domain.com/krakow/mechanik`
2. `https://your-domain.com/ranking/krakow/mechanik`

**Check lead form:**
1. Scroll to lead capture form
2. Fill out test data
3. Submit
4. Verify in database:
```bash
wp db query "SELECT * FROM wp_pt24_leads ORDER BY id DESC LIMIT 1" --allow-root
```

### **Step 5.6: PT24 URL Structure**

**Landing page template:**
```
/{city}/{service}
Example: /krakow/mechanik
Template: single-pt24_landing.php
```

**Ranking page template:**
```
/ranking/{city}/{service}
Example: /ranking/krakow/mechanik
Template: ranking-pt24_landing.php
```

---

## 6️⃣ Monetization Configuration

### **Step 6.1: Google AdSense Setup**

**Prerequisites:**
- Google AdSense account approved
- Publisher ID (format: `pub-XXXXXXXXXXXXXXXX`)

**Configure in Admin Panel:**

1. Go to **Monetization Tab**
2. Enter your **AdSense Publisher ID**
3. Select **Placement Strategy:**
   - **Aggressive:** 6 ad units (maximum revenue)
   - **Balanced:** 4 ad units (recommended)
   - **Conservative:** 2 ad units (best UX)
   - **Funnel-Aware:** Smart placement based on content type

### **Step 6.2: Configure Ad Placements**

**Enable/disable specific placements:**

- [x] **Header Ad** - Below site header
- [x] **In-Content Ad** - After 3rd paragraph
- [x] **Sidebar Ad** - Right sidebar (desktop only)
- [x] **Footer Ad** - Above footer
- [x] **Between Posts** - In article lists
- [x] **Sticky Mobile Ad** - Fixed bottom on mobile

**Recommended starting configuration:**
- ✓ In-Content Ad
- ✓ Sidebar Ad (if you have sidebar)
- ✓ Footer Ad

### **Step 6.3: Funnel-Aware Strategy**

**How it works:**

The system automatically detects content type:
- **TOFU (Top of Funnel):** Awareness content → Full ads (2 units)
- **MOFU (Middle of Funnel):** Consideration content → Limited ads (1 unit)
- **BOFU (Bottom of Funnel):** Conversion content → No ads (better conversion)

**Configure funnel detection:**
```bash
wp option update pearblog_adsense_enable_tofu 1 --allow-root
wp option update pearblog_adsense_enable_mofu 1 --allow-root
wp option update pearblog_adsense_enable_bofu 0 --allow-root
```

### **Step 6.4: Affiliate Marketing Setup**

**Configure affiliate programs:**

1. Go to **Monetization Tab** → **Affiliate Section**
2. Add affiliate programs:
   ```
   Program Name: Amazon Associates
   Tracking ID: your-id-20
   Disclosure Text: "This post contains affiliate links..."
   ```
3. Enable auto-disclosure: ✓

**Insert affiliate links in content:**
- System automatically adds affiliate links based on keywords
- Configure keywords in Monetization settings

### **Step 6.5: Sponsored Content**

**Mark posts as sponsored:**
```bash
wp post meta update POST_ID pearblog_is_sponsored 1 --allow-root
```

**Add sponsor badge:**
- Automatically displays "Sponsored" badge
- Configure badge style in Monetization settings

### **Step 6.6: Track Revenue**

**View revenue reports:**

1. Go to **Analytics Tab**
2. Click **Revenue** section
3. View metrics:
   - Today's revenue
   - This week
   - This month
   - Top-earning articles
   - RPM (Revenue per 1000 views)

**Export revenue data:**
```bash
wp pearblog export-revenue --from=2026-01-01 --to=2026-12-31 --allow-root
```

---

## 7️⃣ SEO Optimization

### **Step 7.1: Configure SEO Settings**

**Go to SEO Tab:**

**Enable core features:**
- ✓ **Internal Linking:** 5-10 links per article
- ✓ **Schema.org Markup:** Structured data
- ✓ **Meta Descriptions:** Auto-generated
- ✓ **Image Alt Text:** Auto-optimized
- ✓ **Sitemap Generation:** XML sitemap

### **Step 7.2: Schema.org Structured Data**

**Available schema types:**
- Article
- BlogPosting
- FAQPage
- HowTo
- Review
- LocalBusiness (for PT24 pages)

**Configure default schema:**
```bash
wp option update pearblog_default_schema 'Article' --allow-root
```

**Verify schema markup:**
1. Visit any article
2. View page source
3. Look for `<script type="application/ld+json">`
4. Test with Google Rich Results Test: https://search.google.com/test/rich-results

### **Step 7.3: Internal Linking**

**How it works:**
- System automatically adds 5-10 relevant internal links
- Links to related articles based on keywords
- Maintains natural anchor text

**Configure linking rules:**
```bash
wp option update pearblog_internal_links_min 5 --allow-root
wp option update pearblog_internal_links_max 10 --allow-root
```

**Manual internal linking:**
```bash
wp pearblog rebuild-links --post-id=123 --allow-root
```

### **Step 7.4: Generate and Submit Sitemap**

**Generate sitemap:**
```bash
wp pearblog generate-sitemap --allow-root
```

**Sitemap URL:**
```
https://your-domain.com/sitemap.xml
```

**Submit to search engines:**

**Google Search Console:**
1. Go to: https://search.google.com/search-console
2. Add your property
3. Go to **Sitemaps**
4. Add sitemap URL: `https://your-domain.com/sitemap.xml`
5. Submit

**Bing Webmaster Tools:**
1. Go to: https://www.bing.com/webmasters
2. Add site
3. Submit sitemap: `https://your-domain.com/sitemap.xml`

### **Step 7.5: Meta Tags Optimization**

**Auto-generated for each post:**
- Title tag (SEO-optimized, 60 chars)
- Meta description (155 chars)
- Open Graph tags (Facebook/LinkedIn)
- Twitter Card tags

**Verify meta tags:**
```bash
wp pearblog check-seo --post-id=123 --allow-root
```

### **Step 7.6: Image Optimization**

**Enable image optimization:**
```bash
wp option update pearblog_enable_image_generation 1 --allow-root
```

**Configure alt text generation:**
- Automatic alt text for all images
- Descriptive, keyword-rich
- Improves accessibility + SEO

---

## 8️⃣ Analytics & Monitoring

### **Step 8.1: Enable Analytics**

**Go to Analytics Tab:**

**Configure tracking:**
- ✓ Page views tracking
- ✓ User engagement
- ✓ Bounce rate
- ✓ Time on page
- ✓ Conversion tracking

### **Step 8.2: View Dashboard Metrics**

**Dashboard Tab shows:**

**Content Metrics:**
- Posts published (today/week/month)
- Total articles
- Average word count
- Publishing rate

**Traffic Metrics:**
- Page views
- Unique visitors
- Bounce rate
- Pages per session
- Average session duration

**Revenue Metrics:**
- Today's revenue
- This week/month
- RPM (Revenue per 1000 views)
- Top earning articles

**SEO Metrics:**
- SEO score
- Indexed pages
- Backlinks (if configured)
- Domain authority

### **Step 8.3: Health Monitoring**

**Check system health:**
```bash
curl https://your-domain.com/wp-json/pearblog/v1/health \
  -H "X-PearBlog-Health-Secret: your-secret"
```

**Expected response:**
```json
{
  "status": "healthy",
  "checks": {
    "database": "ok",
    "ai_connection": "ok",
    "disk_space": "ok",
    "memory": "ok"
  },
  "last_pipeline_run": "2026-05-03 19:00:00",
  "queue_size": 5
}
```

**Set health check secret:**
```bash
wp option update pearblog_health_secret "your-random-secret-here" --allow-root
```

### **Step 8.4: Error Tracking**

**View error log:**
```bash
tail -f /var/www/your-domain.com/wp-content/debug.log
```

**View PearBlog errors:**
```bash
tail -f /var/www/your-domain.com/wp-content/pearblog-engine.log
```

**Check error tracker (v8.3+):**
```bash
wp pearblog errors --last=24h --allow-root
```

### **Step 8.5: Performance Monitoring**

**View performance dashboard:**
1. Go to **Analytics Tab**
2. Click **Performance** section
3. View:
   - Page load time
   - Database query time
   - API response time
   - Memory usage

**Run performance audit:**
```bash
wp pearblog audit-performance --allow-root
```

### **Step 8.6: Configure Alerts**

**Email alerts for critical events:**

**Enable alerts:**
```bash
wp option update pearblog_enable_alerts 1 --allow-root
wp option update pearblog_alert_email "admin@your-domain.com" --allow-root
```

**Alert triggers:**
- Pipeline failures
- API errors
- Low disk space
- High error rate
- Content generation failures

---

## 9️⃣ Lead Management

### **Step 9.1: View Leads (PT24)**

**Via Admin Panel:**
1. Go to **Leads Tab**
2. View lead list with:
   - Name, Email, Phone
   - Service requested
   - City/Location
   - Message
   - Date submitted

**Via WP-CLI:**
```bash
# View recent leads
wp db query "SELECT * FROM wp_pt24_leads ORDER BY created_at DESC LIMIT 10" --allow-root

# Count leads
wp db query "SELECT COUNT(*) FROM wp_pt24_leads" --allow-root

# Leads by service
wp db query "SELECT service, COUNT(*) as count FROM wp_pt24_leads GROUP BY service" --allow-root
```

### **Step 9.2: Export Leads**

**Export to CSV:**
```bash
wp pearblog export-leads --from=2026-01-01 --to=2026-12-31 --output=leads.csv --allow-root
```

**Via Admin Panel:**
1. Go to **Leads Tab**
2. Select date range
3. Click **Export CSV**
4. Download file

**CSV format:**
```csv
id,name,email,phone,service,city,message,created_at
1,Jan Kowalski,jan@email.com,+48123456789,mechanik,krakow,"Need car repair",2026-05-03 10:30:00
```

### **Step 9.3: Lead Notifications**

**Email notifications:**
```bash
wp option update pearblog_lead_notifications 1 --allow-root
wp option update pearblog_lead_notification_email "sales@your-domain.com" --allow-root
```

**Webhook notifications:**
```bash
wp option update pearblog_lead_webhook "https://your-crm.com/webhook" --allow-root
```

### **Step 9.4: CRM Integration**

**REST API endpoint:**
```bash
# Get leads via API
curl -X GET https://your-domain.com/wp-json/pearblog/v1/leads \
  -H "Authorization: Bearer YOUR_API_KEY"
```

**Create API key:**
```bash
wp option update pearblog_api_key "$(openssl rand -hex 32)" --allow-root
wp option get pearblog_api_key --allow-root
```

**Webhook payload format:**
```json
{
  "event": "lead_created",
  "lead": {
    "id": 123,
    "name": "Jan Kowalski",
    "email": "jan@email.com",
    "phone": "+48123456789",
    "service": "mechanik",
    "city": "krakow",
    "message": "Need car repair",
    "created_at": "2026-05-03T10:30:00Z"
  }
}
```

---

## 🔟 Multi-Site Management

### **Step 10.1: Enable WordPress Multisite**

**Configure wp-config.php:**
```bash
nano /var/www/your-domain.com/wp-config.php
```

**Add before "That's all, stop editing!":**
```php
/* Multisite */
define( 'WP_ALLOW_MULTISITE', true );
```

**Run multisite setup:**
```bash
wp core multisite-convert --allow-root
```

### **Step 10.2: Add New Site**

**Via WP-CLI:**
```bash
wp site create --slug=site2 --title="Site 2" --email="admin@site2.com" --allow-root
```

**Via Admin Panel:**
1. Go to **My Sites** → **Network Admin** → **Sites**
2. Click **Add New**
3. Fill in details:
   - Site Address (URL)
   - Site Title
   - Admin Email
4. Click **Add Site**

### **Step 10.3: Configure Per-Site Settings**

**Switch to site:**
```bash
wp option update pearblog_industry "Technology" --url=https://site2.your-domain.com --allow-root
```

**Set different AI model per site:**
```bash
wp option update pearblog_ai_model "gpt-4" --url=https://site2.your-domain.com --allow-root
```

### **Step 10.4: Centralized Configuration**

**Network-wide settings:**
- Set in Network Admin
- Apply to all sites
- Individual sites can override

**Set network option:**
```bash
wp site option update pearblog_network_api_key "YOUR_KEY" --allow-root
```

### **Step 10.5: Usage Metering**

**Track usage per site:**
```bash
wp pearblog usage --site-id=2 --allow-root
```

**Set usage limits:**
```bash
wp site meta update 2 pearblog_monthly_limit 100 --allow-root
```

---

## 1️⃣1️⃣ Backup & Recovery

### **Step 11.1: Manual Database Backup**

**Create backup:**
```bash
# Full database backup
wp db export /backups/db-$(date +%Y%m%d-%H%M%S).sql --allow-root

# Specific tables only
wp db export /backups/db-content-$(date +%Y%m%d).sql --tables=wp_posts,wp_postmeta --allow-root
```

### **Step 11.2: Manual Files Backup**

**Backup uploads:**
```bash
cd /var/www/your-domain.com
tar -czf /backups/uploads-$(date +%Y%m%d).tar.gz wp-content/uploads/
```

**Backup entire site:**
```bash
tar -czf /backups/site-full-$(date +%Y%m%d).tar.gz /var/www/your-domain.com/
```

### **Step 11.3: Automated Backup Setup**

**Create backup script:**
```bash
nano /root/backup-pearblog.sh
```

**Script content:**
```bash
#!/bin/bash
DATE=$(date +%Y%m%d-%H%M%S)
BACKUP_DIR="/backups"
SITE_DIR="/var/www/your-domain.com"

# Create backup directory
mkdir -p $BACKUP_DIR

# Database backup
cd $SITE_DIR
wp db export $BACKUP_DIR/db-$DATE.sql --allow-root

# Files backup
tar -czf $BACKUP_DIR/files-$DATE.tar.gz wp-content/

# Delete backups older than 30 days
find $BACKUP_DIR -name "*.sql" -mtime +30 -delete
find $BACKUP_DIR -name "*.tar.gz" -mtime +30 -delete

echo "Backup completed: $DATE"
```

**Make executable:**
```bash
chmod +x /root/backup-pearblog.sh
```

**Schedule with cron:**
```bash
crontab -e
```

**Add line (runs daily at 2 AM):**
```
0 2 * * * /root/backup-pearblog.sh >> /var/log/pearblog-backup.log 2>&1
```

### **Step 11.4: Restore from Backup**

**Restore database:**
```bash
wp db import /backups/db-20260503-140000.sql --allow-root
```

**Restore files:**
```bash
cd /var/www/your-domain.com
tar -xzf /backups/files-20260503-140000.tar.gz
```

**Verify after restore:**
```bash
wp db check --allow-root
wp core verify-checksums --allow-root
```

### **Step 11.5: Offsite Backup**

**Upload to remote server:**
```bash
# Using rsync
rsync -avz /backups/ user@backup-server:/remote/backups/

# Using S3
aws s3 sync /backups/ s3://your-bucket/pearblog-backups/
```

---

## 1️⃣2️⃣ Troubleshooting

### **Step 12.1: Content Not Generating**

**Check AI connection:**
```bash
wp pearblog test-ai --allow-root
```

**Check API key:**
```bash
wp option get pearblog_openai_api_key --allow-root
```

**Check queue:**
```bash
wp pearblog queue-status --allow-root
```

**Check logs:**
```bash
tail -50 /var/www/your-domain.com/wp-content/pearblog-engine.log | grep ERROR
```

### **Step 12.2: PT24 Pages Not Loading**

**Check rewrite rules:**
```bash
wp rewrite flush --allow-root
```

**Check PT24 posts exist:**
```bash
wp post list --post_type=pt24_landing --allow-root
```

**Regenerate pages:**
```bash
wp pt24 generate --all --force --allow-root
```

### **Step 12.3: High Memory Usage**

**Check PHP memory limit:**
```bash
php -i | grep memory_limit
```

**Increase if needed (in php.ini):**
```
memory_limit = 256M
```

**Check WordPress memory:**
```bash
wp eval "echo WP_MEMORY_LIMIT;" --allow-root
```

**Increase in wp-config.php:**
```php
define( 'WP_MEMORY_LIMIT', '256M' );
```

### **Step 12.4: Slow Page Load**

**Enable caching:**
```bash
wp plugin install wp-super-cache --activate --allow-root
```

**Check database performance:**
```bash
wp db optimize --allow-root
```

**Check slow queries:**
```bash
wp db query "SHOW PROCESSLIST" --allow-root
```

### **Step 12.5: SSL Certificate Issues**

**Renew certificate:**
```bash
certbot renew --nginx
```

**Test SSL:**
```bash
curl -I https://your-domain.com
```

**Force HTTPS:**
```bash
wp search-replace 'http://your-domain.com' 'https://your-domain.com' --allow-root
```

### **Step 12.6: Get Help**

**Check documentation:**
- TROUBLESHOOTING.md
- SYSTEM-ARCHITECTURE-MAP.md
- API-DOCUMENTATION.md

**GitHub Issues:**
https://github.com/AndyPearman89/PearBlog-Engine-/issues

---

## 1️⃣3️⃣ Advanced Operations

### **Step 13.1: Custom Content Templates**

**Create custom template:**
```bash
nano /var/www/your-domain.com/wp-content/mu-plugins/pearblog-engine/src/Content/Templates/CustomTemplate.php
```

**Register template:**
```php
add_filter( 'pearblog_content_templates', function( $templates ) {
    $templates['custom'] = 'Custom Template';
    return $templates;
});
```

### **Step 13.2: Custom WP-CLI Commands**

**Add to PearBlogCommand.php:**
```php
/**
 * Custom command
 */
public function custom_command( $args, $assoc_args ) {
    WP_CLI::success( 'Custom command executed!' );
}
```

**Usage:**
```bash
wp pearblog custom-command --allow-root
```

### **Step 13.3: Webhook Integration**

**Configure webhook URL:**
```bash
wp option update pearblog_webhook_url "https://your-service.com/webhook" --allow-root
```

**Available events:**
- `pearblog_pipeline_completed`
- `pearblog_post_published`
- `pearblog_lead_created`
- `pearblog_error_occurred`

### **Step 13.4: Custom Monetization Rules**

**Add custom placement:**
```php
add_filter( 'pearblog_ad_placements', function( $placements ) {
    $placements['custom_position'] = [
        'name' => 'Custom Position',
        'location' => 'after_paragraph',
        'paragraph' => 5,
    ];
    return $placements;
});
```

### **Step 13.5: API Development**

**Add custom REST endpoint:**
```php
add_action( 'rest_api_init', function() {
    register_rest_route( 'pearblog/v1', '/custom', [
        'methods' => 'GET',
        'callback' => 'custom_endpoint_handler',
        'permission_callback' => function() {
            return current_user_can( 'manage_options' );
        }
    ]);
});
```

---

## 1️⃣4️⃣ Daily Operations Checklist

### **Morning Routine (5 minutes)**

- [x] Check system health: `wp pearblog health --allow-root`
- [x] Review content published overnight
- [x] Check error log for issues
- [x] Monitor queue size

### **Weekly Tasks (30 minutes)**

- [x] Review analytics dashboard
- [x] Check revenue reports
- [x] Analyze top-performing content
- [x] Add new topics to queue
- [x] Review and respond to leads
- [x] Check backup integrity
- [x] Update plugins/themes if needed

### **Monthly Tasks (1-2 hours)**

- [x] Full system audit
- [x] Performance optimization review
- [x] SEO report analysis
- [x] Content strategy review
- [x] Test backup restoration
- [x] Review and update monetization strategy
- [x] Clean up old drafts and revisions
- [x] Database optimization

---

## 🎯 Quick Command Reference

### **Content Generation**
```bash
wp pearblog add-topic "Topic name" --allow-root
wp pearblog generate --count=5 --allow-root
wp pearblog autopilot start --allow-root
wp pearblog autopilot status --allow-root
```

### **PT24 Management**
```bash
wp pt24 generate --all --allow-root
wp pt24 list --allow-root
wp pt24 generate --service=mechanik --city=krakow --allow-root
```

### **System Management**
```bash
wp pearblog health --allow-root
wp pearblog status --allow-root
wp db export backup.sql --allow-root
wp cache flush --allow-root
```

### **Monitoring**
```bash
tail -f /var/www/your-domain.com/wp-content/pearblog-engine.log
wp pearblog errors --last=24h --allow-root
```

---

## ✅ Success Criteria

You've successfully set up PearBlog Engine when:

- ✅ WordPress and PearBlog Engine are installed
- ✅ AI connection is active and working
- ✅ At least 5 articles auto-generated
- ✅ PT24 landing pages are live
- ✅ Lead capture forms are working
- ✅ Monetization is configured (if applicable)
- ✅ SEO features are enabled
- ✅ Sitemap submitted to search engines
- ✅ Automated backups are running
- ✅ Monitoring/alerts are configured

---

**🎉 Congratulations! Your PearBlog Engine is fully operational.**

For additional help, see:
- SYSTEM-ARCHITECTURE-MAP.md
- TROUBLESHOOTING.md
- API-DOCUMENTATION.md
- GitHub: https://github.com/AndyPearman89/PearBlog-Engine-
