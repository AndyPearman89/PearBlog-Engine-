# 🚀 Deployment Guide: elektryk.pt24.pro

**Domain:** elektryk.pt24.pro
**Target:** Electrician services vertical - Car electrical services platform
**Purpose:** Local electrician directory connecting car electrical specialists with customers
**Engine:** PearBlog Engine v7.0 + PT24 Platform v2.0

---

## Quick Deploy Summary

**What:** Deploy elektryk.pt24.pro as a subdomain for car electrician services
**Services:** Car diagnostics, starter motors, alternators, car alarms, electrical installations
**Model:** SEO → Landing Page → Lead Capture → Business Contact → Revenue

---

## Table of Contents

1. [Overview](#1-overview)
2. [Pre-Requirements](#2-pre-requirements)
3. [Deployment Options](#3-deployment-options)
4. [Option A: Subdomain Setup (Recommended)](#4-option-a-subdomain-setup-recommended)
5. [Option B: Subdirectory Setup](#5-option-b-subdirectory-setup)
6. [Content Generation](#6-content-generation)
7. [Testing & Verification](#7-testing--verification)
8. [SEO Configuration](#8-seo-configuration)
9. [Monitoring](#9-monitoring)
10. [Troubleshooting](#10-troubleshooting)

---

## 1. Overview

### What is elektryk.pt24.pro?

A dedicated vertical for **car electrician services** (elektryk samochodowy) connecting:
- Customers with electrical problems (dead battery, starter issues, alarm installation)
- Local car electricians and electrical workshops
- Mobile electrical services

### Key Features

- **Service-Specific Landing Pages**: `elektryk.pt24.pro/{miasto}/`
- **Lead Capture Forms**: Direct contact to local electricians
- **Business Listings**: Verified electrician profiles
- **Mobile-First Design**: Optimized for users with car breakdowns
- **24/7 Availability**: Emergency electrician services

### Target Keywords

```
- "elektryk samochodowy {miasto}"
- "naprawa instalacji elektrycznej {miasto}"
- "wymiana alternatora {miasto}"
- "naprawa stacyjki {miasto}"
- "elektryk mobilny {miasto}"
- "diagnoza elektryczna {miasto}"
```

---

## 2. Pre-Requirements

### Server Requirements

✅ **Base PT24 Platform Deployed:**
- WordPress 6.0+
- PearBlog Engine v7.0
- PT24 MU Plugin active
- PHP 8.1+
- MySQL 5.7+

✅ **DNS Configuration:**
```bash
# Add subdomain A record:
elektryk.pt24.pro → Your server IP

# Verify DNS propagation:
dig elektryk.pt24.pro +short
```

✅ **SSL Certificate:**
```bash
# Extend certificate for subdomain:
certbot certonly --expand -d pt24.pro -d www.pt24.pro -d elektryk.pt24.pro
```

---

## 3. Deployment Options

### Option A: Subdomain (Recommended)

**Best for:**
- Dedicated branding (`elektryk.pt24.pro`)
- Better SEO isolation
- Vertical-specific customization
- Independent scaling

**Setup Time:** 30-60 minutes

### Option B: Subdirectory

**Best for:**
- Shared WordPress instance
- Simpler maintenance
- Lower hosting costs

**Setup Time:** 15-30 minutes

---

## 4. Option A: Subdomain Setup (Recommended)

### Step 1: Create WordPress Multisite Network

If not already configured:

```bash
cd /var/www/pt24.pro

# Add to wp-config.php (before "That's all, stop editing!"):
cat >> wp-config.php <<'EOF'

/* Multisite */
define('WP_ALLOW_MULTISITE', true);
EOF

# Activate multisite in WordPress Admin:
# Tools → Network Setup → Subdomain
# Follow on-screen instructions
```

### Step 2: Add Elektryk Subdomain to Multisite

```bash
# Via WP-CLI:
wp site create \
  --slug=elektryk \
  --title="Elektryk PT24 - Elektryka Samochodowa" \
  --email="admin@elektryk.pt24.pro" \
  --allow-root

# Get site ID:
SITE_ID=$(wp site list --field=blog_id --url=elektryk.pt24.pro --allow-root)
echo "Elektryk site ID: $SITE_ID"
```

### Step 3: Configure Web Server for Subdomain

#### Apache Configuration

```bash
# Multisite handles subdomains automatically via .htaccess
# Ensure wildcard ServerAlias is in place:

cat > /etc/apache2/sites-available/pt24-pro.conf <<'EOF'
<VirtualHost *:80>
    ServerName pt24.pro
    ServerAlias www.pt24.pro
    ServerAlias *.pt24.pro
    DocumentRoot /var/www/pt24.pro

    <Directory /var/www/pt24.pro>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/pt24-error.log
    CustomLog ${APACHE_LOG_DIR}/pt24-access.log combined
</VirtualHost>
EOF

# Reload Apache:
systemctl reload apache2
```

#### Nginx Configuration

```bash
# Update server block for wildcard subdomain:

cat > /etc/nginx/sites-available/pt24-pro <<'EOF'
server {
    listen 80;
    listen [::]:80;
    server_name pt24.pro www.pt24.pro *.pt24.pro;
    root /var/www/pt24.pro;
    index index.php index.html;

    client_max_body_size 100M;

    location / {
        try_files $uri $uri/ /index.php?$args;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }

    location ~ /\.ht {
        deny all;
    }
}
EOF

# Test and reload:
nginx -t
systemctl reload nginx
```

### Step 4: Configure SSL for Subdomain

```bash
# Expand SSL certificate:
certbot certonly --expand \
  -d pt24.pro \
  -d www.pt24.pro \
  -d elektryk.pt24.pro \
  --non-interactive --agree-tos --email admin@pt24.pro

# For Apache:
certbot --apache -d elektryk.pt24.pro

# For Nginx:
certbot --nginx -d elektryk.pt24.pro
```

### Step 5: Activate Theme on Subdomain

```bash
# Switch to elektryk subdomain:
wp theme activate pearblog-theme \
  --url=elektryk.pt24.pro \
  --allow-root

# Verify theme is active:
wp theme list --url=elektryk.pt24.pro --allow-root
```

### Step 6: Configure Elektryk Site Settings

```bash
# Set site profile for elektryk vertical:
wp option update pearblog_industry 'local_services' \
  --url=elektryk.pt24.pro \
  --allow-root

wp option update pearblog_language 'pl' \
  --url=elektryk.pt24.pro \
  --allow-root

wp option update pearblog_homepage_version 'v7' \
  --url=elektryk.pt24.pro \
  --allow-root

# Set site title and tagline:
wp option update blogname 'Elektryk PT24 - Elektryka Samochodowa w Twojej Okolicy' \
  --url=elektryk.pt24.pro \
  --allow-root

wp option update blogdescription 'Znajdź sprawdzonego elektryka samochodowego. Diagnoza, naprawa instalacji, alternatory, stacyjki.' \
  --url=elektryk.pt24.pro \
  --allow-root
```

---

## 5. Option B: Subdirectory Setup

### Alternative: Path-Based Routing

If subdomain is not preferred, use path-based structure:

```
pt24.pro/elektryk/{miasto}/
```

**Implementation:**

```bash
# Generate pages with elektryk service:
wp pt24 generate-pages --service=elektryk --allow-root

# Pages will be accessible at:
# pt24.pro/elektryk/warszawa/
# pt24.pro/elektryk/krakow/
# etc.
```

**Note:** This uses the existing PT24 landing page system without subdomain setup.

---

## 6. Content Generation

### Initialize PT24 for Elektryk Vertical

```bash
# Ensure PT24 is initialized (if not already done):
wp pt24 init --url=elektryk.pt24.pro --allow-root

# This creates:
# - Service categories (including elektryk)
# - Top 20 cities
# - Database tables
# - Rewrite rules
```

### Generate Landing Pages for Elektryk

```bash
# Generate elektryk pages for all cities:
wp pt24 generate-pages \
  --service=elektryk \
  --batch=50 \
  --url=elektryk.pt24.pro \
  --allow-root

# Or generate specific cities:
wp pt24 generate-pages \
  --service=elektryk \
  --city=warszawa \
  --url=elektryk.pt24.pro \
  --allow-root

wp pt24 generate-pages \
  --service=elektryk \
  --city=krakow \
  --url=elektryk.pt24.pro \
  --allow-root
```

### Verify Generated Pages

```bash
# List all elektryk landing pages:
wp post list \
  --post_type=pt24_landing \
  --url=elektryk.pt24.pro \
  --allow-root

# Check platform statistics:
wp pt24 stats --url=elektryk.pt24.pro --allow-root
```

### Expected URL Structure

After generation, pages will be accessible at:

```
Landing Pages:
- elektryk.pt24.pro/warszawa/
- elektryk.pt24.pro/krakow/
- elektryk.pt24.pro/wroclaw/
- elektryk.pt24.pro/poznan/
- elektryk.pt24.pro/gdansk/
... (20+ cities)

Ranking Pages (automatic):
- elektryk.pt24.pro/ranking/warszawa/
- elektryk.pt24.pro/ranking/krakow/
- elektryk.pt24.pro/ranking/wroclaw/
... etc.
```

---

## 7. Testing & Verification

### Test Subdomain Access

```bash
# Test homepage:
curl -I https://elektryk.pt24.pro
# Should return: HTTP/2 200

# Test landing page:
curl -I https://elektryk.pt24.pro/warszawa/
# Should return: HTTP/2 200

# Test ranking page:
curl -I https://elektryk.pt24.pro/ranking/warszawa/
# Should return: HTTP/2 200
```

### Test API Endpoints

```bash
# Test PT24 businesses API:
curl https://elektryk.pt24.pro/wp-json/pt24/v1/businesses

# Test health endpoint:
curl https://elektryk.pt24.pro/wp-json/pearblog/v1/health
```

### Test Lead Form Submission

Visit any landing page and test the lead form:

```
1. Go to: https://elektryk.pt24.pro/warszawa/
2. Fill out the form:
   - Name: Test User
   - Phone: +48 123 456 789
   - Email: test@example.com
   - Problem: "Nie odpala stacyjka"
3. Check database:
```

```bash
wp db query "SELECT * FROM wp_pt24_leads WHERE service='elektryk' ORDER BY id DESC LIMIT 1" \
  --url=elektryk.pt24.pro \
  --allow-root
```

### Manual Browser Testing

**Desktop:**
1. Visit https://elektryk.pt24.pro
2. Navigate to city page
3. Verify form submission works
4. Check ranking page display
5. Test all CTAs

**Mobile:**
1. Use Chrome DevTools mobile emulation
2. Test responsive layout
3. Verify sticky CTA button
4. Test form on mobile
5. Check load speed

---

## 8. SEO Configuration

### Update Site URLs

```bash
# Ensure HTTPS URLs:
wp option update home 'https://elektryk.pt24.pro' \
  --url=elektryk.pt24.pro \
  --allow-root

wp option update siteurl 'https://elektryk.pt24.pro' \
  --url=elektryk.pt24.pro \
  --allow-root
```

### Configure Permalinks

```bash
# Flush rewrite rules:
wp rewrite flush --url=elektryk.pt24.pro --allow-root

# Verify permalink structure:
wp option get permalink_structure --url=elektryk.pt24.pro --allow-root
```

### Add Structured Data

Landing pages automatically include structured data for:
- LocalBusiness schema
- Service schema
- BreadcrumbList schema

### Submit to Google

```bash
# Generate sitemap:
wp sitemap generate --url=elektryk.pt24.pro --allow-root

# Sitemap URL:
# https://elektryk.pt24.pro/wp-sitemap.xml

# Submit to Google Search Console:
# 1. Verify elektryk.pt24.pro domain
# 2. Submit sitemap URL
# 3. Request indexing for key pages
```

---

## 9. Monitoring

### Monitor Landing Pages

```bash
# Check elektryk pages count:
wp post list \
  --post_type=pt24_landing \
  --format=count \
  --url=elektryk.pt24.pro \
  --allow-root

# View recent leads:
wp db query "SELECT * FROM wp_pt24_leads WHERE service='elektryk' ORDER BY created_at DESC LIMIT 10" \
  --url=elektryk.pt24.pro \
  --allow-root
```

### Monitor Traffic & Conversions

**Key Metrics to Track:**
- Page views per city
- Form submissions (leads)
- Conversion rate (visitors → leads)
- Phone clicks
- Email clicks

**Google Analytics 4 Setup:**

```javascript
// Add to elektryk.pt24.pro
gtag('config', 'G-XXXXXXXXXX', {
  'custom_map': {
    'dimension1': 'service_type',
    'dimension2': 'city'
  }
});

// Track form submissions:
gtag('event', 'generate_lead', {
  'service_type': 'elektryk',
  'city': 'warszawa'
});
```

### Health Check Script

```bash
#!/bin/bash
# health-check-elektryk.sh

echo "=== Elektryk.PT24.PRO Health Check ==="
echo ""

# Check subdomain accessibility
echo "Testing subdomain..."
curl -s -o /dev/null -w "Status: %{http_code}\n" https://elektryk.pt24.pro

# Check landing page
echo "Testing landing page..."
curl -s -o /dev/null -w "Status: %{http_code}\n" https://elektryk.pt24.pro/warszawa/

# Check API
echo "Testing API..."
curl -s -o /dev/null -w "Status: %{http_code}\n" https://elektryk.pt24.pro/wp-json/pt24/v1/businesses

# Check database
echo "Leads in database:"
wp db query "SELECT COUNT(*) as total FROM wp_pt24_leads WHERE service='elektryk'" \
  --url=elektryk.pt24.pro \
  --allow-root \
  --skip-column-names

echo ""
echo "=== Health Check Complete ==="
```

---

## 10. Troubleshooting

### Issue: Subdomain not accessible

```bash
# Check DNS:
dig elektryk.pt24.pro +short
# Should return server IP

# Check web server config:
# Apache:
apache2ctl -t
grep -r "ServerAlias.*pt24.pro" /etc/apache2/sites-enabled/

# Nginx:
nginx -t
grep -r "server_name.*pt24.pro" /etc/nginx/sites-enabled/
```

### Issue: SSL certificate errors

```bash
# Check certificate:
certbot certificates

# Renew if needed:
certbot renew --force-renewal

# Verify SSL:
openssl s_client -connect elektryk.pt24.pro:443 -servername elektryk.pt24.pro
```

### Issue: Pages return 404

```bash
# Flush rewrite rules:
wp rewrite flush --url=elektryk.pt24.pro --allow-root

# Re-initialize PT24:
wp pt24 init --url=elektryk.pt24.pro --allow-root

# Check if pages exist:
wp post list --post_type=pt24_landing --url=elektryk.pt24.pro --allow-root
```

### Issue: Form not submitting

```bash
# Check AJAX handler:
curl -X POST https://elektryk.pt24.pro/wp-admin/admin-ajax.php \
  -d "action=pt24_submit_lead" \
  -d "service=elektryk" \
  -d "city=warszawa" \
  -d "name=Test" \
  -d "phone=123456789" \
  -d "consent=1"

# Verify database table exists:
wp db query "SHOW TABLES LIKE 'wp_pt24_leads'" --url=elektryk.pt24.pro --allow-root
```

### Issue: Multisite subdomain not working

```bash
# Check multisite configuration in wp-config.php:
grep -A 5 "MULTISITE" /var/www/pt24.pro/wp-config.php

# Should include:
# define('MULTISITE', true);
# define('SUBDOMAIN_INSTALL', true);
# define('DOMAIN_CURRENT_SITE', 'pt24.pro');

# Check .htaccess has multisite rules:
head -30 /var/www/pt24.pro/.htaccess
```

---

## Maintenance Commands

### Regular Maintenance

```bash
# Update platform statistics:
wp pt24 stats --url=elektryk.pt24.pro --allow-root

# Optimize database:
wp db optimize --url=elektryk.pt24.pro --allow-root

# Clear caches:
wp cache flush --url=elektryk.pt24.pro --allow-root

# Check for broken pages:
wp post list \
  --post_type=pt24_landing \
  --post_status=publish \
  --url=elektryk.pt24.pro \
  --allow-root
```

### Add New Cities

```bash
# Generate pages for a new city:
wp pt24 generate-pages \
  --service=elektryk \
  --city=lublin \
  --url=elektryk.pt24.pro \
  --allow-root
```

### Backup Elektryk Subdomain

```bash
#!/bin/bash
# backup-elektryk.sh

DATE=$(date +%Y-%m-%d)
BACKUP_DIR="/root/backups/elektryk"

mkdir -p $BACKUP_DIR

# Backup elektryk-specific database tables:
wp db export $BACKUP_DIR/elektryk-db-$DATE.sql \
  --tables=wp_*_pt24_leads,wp_*_pt24_business_stats \
  --url=elektryk.pt24.pro \
  --allow-root

# Backup uploads (if subdomain-specific):
tar -czf $BACKUP_DIR/elektryk-uploads-$DATE.tar.gz \
  /var/www/pt24.pro/wp-content/uploads/sites/$SITE_ID/

echo "Backup completed: $DATE"
```

---

## Next Steps After Deployment

### 1. Add Business Profiles

```bash
# Create test electrician business:
wp post create \
  --post_type=pt24_business \
  --post_title="AutoElektryk Kowalski - Warszawa" \
  --post_content="Profesjonalna diagnostyka i naprawa instalacji elektrycznej. Mobilny serwis 24h." \
  --post_status=publish \
  --url=elektryk.pt24.pro \
  --allow-root

# Add business meta:
BUSINESS_ID=$(wp post list --post_type=pt24_business --format=ids --posts_per_page=1 --url=elektryk.pt24.pro --allow-root)

wp post meta update $BUSINESS_ID pt24_phone '+48 123 456 789' --url=elektryk.pt24.pro --allow-root
wp post meta update $BUSINESS_ID pt24_email 'kontakt@autoelektryk.pl' --url=elektryk.pt24.pro --allow-root
wp post meta update $BUSINESS_ID pt24_service_area 'Warszawa i okolice' --url=elektryk.pt24.pro --allow-root
wp post meta update $BUSINESS_ID pt24_years_experience '15' --url=elektryk.pt24.pro --allow-root
wp post meta update $BUSINESS_ID pt24_mobile_service '1' --url=elektryk.pt24.pro --allow-root
```

### 2. Configure Email Notifications

Edit theme file to set admin email for elektryk leads:

```php
// In theme functions or PT24 integration file:
add_filter('pt24_lead_notification_email', function($email, $lead_data) {
    if ($lead_data['service'] === 'elektryk') {
        return 'leads@elektryk.pt24.pro';
    }
    return $email;
}, 10, 2);
```

### 3. Set Up Analytics Tracking

Track elektryk-specific events:

```javascript
// Track form view:
gtag('event', 'view_form', {
  'service': 'elektryk',
  'city': '<?php echo $city; ?>'
});

// Track form submission:
gtag('event', 'generate_lead', {
  'service': 'elektryk',
  'city': '<?php echo $city; ?>',
  'value': 50.00 // estimated lead value
});
```

### 4. Promote the Platform

**SEO:**
- Submit sitemap to Google Search Console
- Create Google Business Profile
- Build local citations

**Content:**
- Write blog posts about car electrical issues
- Create FAQ pages
- Add how-to guides

**Advertising:**
- Google Ads for "elektryk samochodowy {miasto}"
- Facebook Ads targeting car owners
- Local directories listing

---

## Summary

### What You've Deployed:

✅ **elektryk.pt24.pro subdomain** with dedicated branding
✅ **20-50 city landing pages** for electrical services
✅ **Lead capture system** integrated and working
✅ **Mobile-responsive design** optimized for emergency situations
✅ **SEO-optimized pages** targeting local searches
✅ **Business listing capability** for electrician profiles

### URL Examples:

```
Homepage: https://elektryk.pt24.pro
Landing:  https://elektryk.pt24.pro/warszawa/
Ranking:  https://elektryk.pt24.pro/ranking/warszawa/
Business: https://elektryk.pt24.pro/firma/autoelektryk-kowalski/
```

### Expected Traffic Patterns:

**Keywords:**
- "elektryk samochodowy warszawa" → elektryk.pt24.pro/warszawa/
- "naprawa stacyjki kraków" → elektryk.pt24.pro/krakow/
- "diagnoza elektryczna wrocław" → elektryk.pt24.pro/wroclaw/

**User Journey:**
1. Google search → Landing page
2. View local electricians
3. Submit lead form OR call directly
4. Electrician receives lead
5. Customer gets service

---

**Deployment Guide Version:** 1.0
**Last Updated:** May 4, 2026
**Platform Version:** PT24.PRO v2.0 + Elektryk Vertical
**Engine Version:** PearBlog Engine v7.0
**Status:** Production Ready ✅
