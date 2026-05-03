# 🚀 Deployment: Landing V2 Pro to poradnik.pro

**Target**: poradnik.pro (204.48.27.118)
**Branch**: claude/create-landing-v2-pro
**Status**: Ready for Production Deployment

---

## Quick Deploy (One-Line)

```bash
# SSH to server and run:
ssh root@204.48.27.118 "cd /var/www/poradnik.pro && \
  git -C /tmp/PearBlog-Engine- pull 2>/dev/null || git clone https://github.com/AndyPearman89/PearBlog-Engine-.git /tmp/PearBlog-Engine- && \
  cd /tmp/PearBlog-Engine- && git checkout claude/create-landing-v2-pro && \
  cp -r theme/pearblog-theme/assets/css/v2-pro-neon.css /var/www/poradnik.pro/wp-content/themes/pearblog-theme/assets/css/ && \
  cp -r theme/pearblog-theme/assets/js/v2-pro-mobile.js /var/www/poradnik.pro/wp-content/themes/pearblog-theme/assets/js/ && \
  cp -r theme/pearblog-theme/page-landing-v2-pro.php /var/www/poradnik.pro/wp-content/themes/pearblog-theme/ && \
  cp -r theme/pearblog-theme/template-parts/*v2-pro*.php /var/www/poradnik.pro/wp-content/themes/pearblog-theme/template-parts/ && \
  cp theme/pearblog-theme/functions.php /var/www/poradnik.pro/wp-content/themes/pearblog-theme/ && \
  chown -R www-data:www-data /var/www/poradnik.pro/wp-content/themes/pearblog-theme && \
  cd /var/www/poradnik.pro && wp cache flush --allow-root && \
  echo '✅ Landing V2 Pro deployed successfully!'"
```

---

## Step-by-Step Deployment

### Step 1: SSH to Server

```bash
ssh root@204.48.27.118
```

### Step 2: Clone/Update Repository

```bash
# If first time:
git clone https://github.com/AndyPearman89/PearBlog-Engine-.git /tmp/PearBlog-Engine-

# If already cloned:
cd /tmp/PearBlog-Engine- && git fetch origin

# Checkout Landing V2 Pro branch:
git checkout claude/create-landing-v2-pro
git pull origin claude/create-landing-v2-pro
```

### Step 3: Deploy CSS & JS Assets

```bash
# Deploy CSS (v2-pro-neon.css - 38KB)
cp /tmp/PearBlog-Engine-/theme/pearblog-theme/assets/css/v2-pro-neon.css \
   /var/www/poradnik.pro/wp-content/themes/pearblog-theme/assets/css/

# Deploy JS (v2-pro-mobile.js - 12KB)
cp /tmp/PearBlog-Engine-/theme/pearblog-theme/assets/js/v2-pro-mobile.js \
   /var/www/poradnik.pro/wp-content/themes/pearblog-theme/assets/js/
```

### Step 4: Deploy Page Template

```bash
# Deploy main landing page template
cp /tmp/PearBlog-Engine-/theme/pearblog-theme/page-landing-v2-pro.php \
   /var/www/poradnik.pro/wp-content/themes/pearblog-theme/
```

### Step 5: Deploy Template Parts (7 files)

```bash
# Deploy all V2 Pro template parts
cp /tmp/PearBlog-Engine-/theme/pearblog-theme/template-parts/hero-v2-pro.php \
   /var/www/poradnik.pro/wp-content/themes/pearblog-theme/template-parts/

cp /tmp/PearBlog-Engine-/theme/pearblog-theme/template-parts/ai-panel-v2-pro.php \
   /var/www/poradnik.pro/wp-content/themes/pearblog-theme/template-parts/

cp /tmp/PearBlog-Engine-/theme/pearblog-theme/template-parts/category-blocks-v2-pro.php \
   /var/www/poradnik.pro/wp-content/themes/pearblog-theme/template-parts/

cp /tmp/PearBlog-Engine-/theme/pearblog-theme/template-parts/expert-cards-v2-pro.php \
   /var/www/poradnik.pro/wp-content/themes/pearblog-theme/template-parts/

cp /tmp/PearBlog-Engine-/theme/pearblog-theme/template-parts/faq-v2-pro.php \
   /var/www/poradnik.pro/wp-content/themes/pearblog-theme/template-parts/

cp /tmp/PearBlog-Engine-/theme/pearblog-theme/template-parts/final-cta-v2-pro.php \
   /var/www/poradnik.pro/wp-content/themes/pearblog-theme/template-parts/

cp /tmp/PearBlog-Engine-/theme/pearblog-theme/template-parts/sticky-cta-v2-pro.php \
   /var/www/poradnik.pro/wp-content/themes/pearblog-theme/template-parts/
```

### Step 6: Update functions.php (AJAX Handlers)

```bash
# Backup current functions.php
cp /var/www/poradnik.pro/wp-content/themes/pearblog-theme/functions.php \
   /var/www/poradnik.pro/wp-content/themes/pearblog-theme/functions.php.backup

# Deploy updated functions.php with V2 Pro AJAX handlers
cp /tmp/PearBlog-Engine-/theme/pearblog-theme/functions.php \
   /var/www/poradnik.pro/wp-content/themes/pearblog-theme/
```

### Step 7: Set Permissions

```bash
# Set correct ownership
chown -R www-data:www-data /var/www/poradnik.pro/wp-content/themes/pearblog-theme

# Set correct permissions
find /var/www/poradnik.pro/wp-content/themes/pearblog-theme -type d -exec chmod 755 {} \;
find /var/www/poradnik.pro/wp-content/themes/pearblog-theme -type f -exec chmod 644 {} \;
```

### Step 8: Clear WordPress Cache

```bash
cd /var/www/poradnik.pro

# Flush WordPress cache
wp cache flush --allow-root

# Clear object cache (if Redis/Memcached)
wp cache flush --allow-root
```

### Step 9: Create Landing Page

```bash
cd /var/www/poradnik.pro

# Create new page with V2 Pro template
wp post create \
  --post_type=page \
  --post_title='Landing V2 Pro' \
  --post_status=publish \
  --page_template='page-landing-v2-pro.php' \
  --allow-root

# Get the page ID (note it for next step)
PAGE_ID=$(wp post list --post_type=page --post_title='Landing V2 Pro' --format=ids --allow-root)

echo "Landing page created with ID: $PAGE_ID"
echo "URL: https://poradnik.pro/?page_id=$PAGE_ID"
```

### Step 10: Set as Homepage (Optional)

```bash
cd /var/www/poradnik.pro

# Get the page ID from previous step
PAGE_ID=$(wp post list --post_type=page --post_title='Landing V2 Pro' --format=ids --allow-root)

# Set as front page
wp option update show_on_front 'page' --allow-root
wp option update page_on_front $PAGE_ID --allow-root

echo "Landing V2 Pro is now your homepage!"
echo "Visit: https://poradnik.pro"
```

---

## Verification Checklist

After deployment, verify:

### ✅ File Check
```bash
# Check files exist
ls -lh /var/www/poradnik.pro/wp-content/themes/pearblog-theme/assets/css/v2-pro-neon.css
ls -lh /var/www/poradnik.pro/wp-content/themes/pearblog-theme/assets/js/v2-pro-mobile.js
ls -lh /var/www/poradnik.pro/wp-content/themes/pearblog-theme/page-landing-v2-pro.php
ls -lh /var/www/poradnik.pro/wp-content/themes/pearblog-theme/template-parts/*v2-pro*.php
```

### ✅ PHP Syntax Check
```bash
# Verify no syntax errors
php -l /var/www/poradnik.pro/wp-content/themes/pearblog-theme/page-landing-v2-pro.php
php -l /var/www/poradnik.pro/wp-content/themes/pearblog-theme/functions.php
```

### ✅ Page Access Test
```bash
# Test page loads (replace PAGE_ID)
curl -I https://poradnik.pro/?page_id=PAGE_ID

# Should return HTTP/2 200
```

### ✅ Assets Loading Test
```bash
# Test CSS loads
curl -I https://poradnik.pro/wp-content/themes/pearblog-theme/assets/css/v2-pro-neon.css

# Test JS loads
curl -I https://poradnik.pro/wp-content/themes/pearblog-theme/assets/js/v2-pro-mobile.js

# Both should return HTTP/2 200
```

### ✅ AJAX Endpoints Test
```bash
# Test AI analysis endpoint
curl -X POST https://poradnik.pro/wp-admin/admin-ajax.php \
  -d "action=v2pro_ai_analyze&problem=test&nonce=test" \
  -H "Content-Type: application/x-www-form-urlencoded"

# Should return JSON (even if nonce fails, endpoint exists)
```

### ✅ Mobile Test
```bash
# Test with mobile user agent
curl -A "Mozilla/5.0 (iPhone; CPU iPhone OS 14_0 like Mac OS X)" \
  https://poradnik.pro/?page_id=PAGE_ID

# Should return HTML with v2pro classes
```

---

## Configuration (Optional)

### Customize Hero Text
```bash
cd /var/www/poradnik.pro

wp option update pearblog_v2pro_hero_title "Rozwiąż problem w kilka minut" --allow-root
wp option update pearblog_v2pro_hero_subtitle "Eksperci, porady i konkretne rozwiązania" --allow-root
```

### Add Custom Experts
```php
# Via PHP or wp-cli eval:
wp eval '
$experts = array(
    array(
        "name" => "Jan Kowalski",
        "rating" => 4.9,
        "reviews" => 128,
        "specialty" => "Prawo cywilne",
        "url" => home_url("/eksperci/jan-kowalski"),
    ),
);
update_option("pearblog_v2pro_experts", $experts);
' --allow-root
```

### View Analytics
```bash
cd /var/www/poradnik.pro

# View events
wp option get pearblog_v2pro_events --allow-root --format=json

# View CTA clicks
wp option get pearblog_v2pro_cta_clicks --allow-root --format=json

# View performance metrics
wp option get pearblog_v2pro_performance --allow-root --format=json
```

---

## Rollback (If Needed)

If something goes wrong:

```bash
# Restore functions.php backup
cp /var/www/poradnik.pro/wp-content/themes/pearblog-theme/functions.php.backup \
   /var/www/poradnik.pro/wp-content/themes/pearblog-theme/functions.php

# Delete V2 Pro files
rm /var/www/poradnik.pro/wp-content/themes/pearblog-theme/assets/css/v2-pro-neon.css
rm /var/www/poradnik.pro/wp-content/themes/pearblog-theme/assets/js/v2-pro-mobile.js
rm /var/www/poradnik.pro/wp-content/themes/pearblog-theme/page-landing-v2-pro.php
rm /var/www/poradnik.pro/wp-content/themes/pearblog-theme/template-parts/*v2-pro*.php

# Clear cache
cd /var/www/poradnik.pro
wp cache flush --allow-root
```

---

## Performance Optimization (Post-Deployment)

### Enable Gzip Compression

For Apache, add to `.htaccess`:
```apache
<IfModule mod_deflate.c>
  AddOutputFilterByType DEFLATE text/css application/javascript
</IfModule>
```

For Nginx, add to server block:
```nginx
gzip on;
gzip_types text/css application/javascript;
gzip_min_length 1000;
```

### Browser Caching

Add to `.htaccess`:
```apache
<IfModule mod_expires.c>
  ExpiresActive On
  ExpiresByType text/css "access plus 1 year"
  ExpiresByType application/javascript "access plus 1 year"
</IfModule>
```

### CDN Integration (Optional)

If using CDN, update asset URLs in `functions.php`:
```php
// In pearblog_enqueue_assets() function
$cdn_url = 'https://cdn.poradnik.pro';
wp_enqueue_style('pearblog-v2-pro-neon', $cdn_url . '/v2-pro-neon.css', ...);
```

---

## Monitoring

### Setup Health Check

```bash
# Add to crontab for monitoring
crontab -e

# Add line:
*/5 * * * * curl -s https://poradnik.pro/wp-json/pearblog/v1/health > /dev/null
```

### Monitor Analytics

```bash
# Daily analytics check script
cat > /root/check-v2pro-analytics.sh << 'EOF'
#!/bin/bash
cd /var/www/poradnik.pro
echo "=== V2 Pro Analytics $(date) ==="
echo "Events:"
wp option get pearblog_v2pro_events --allow-root --format=json | jq .
echo ""
echo "CTA Clicks:"
wp option get pearblog_v2pro_cta_clicks --allow-root --format=json | jq .
EOF

chmod +x /root/check-v2pro-analytics.sh

# Run daily
echo "0 9 * * * /root/check-v2pro-analytics.sh | mail -s 'V2 Pro Analytics' admin@poradnik.pro" | crontab -
```

---

## Success Criteria

✅ Deployment successful when:

1. All files deployed (11 files)
2. PHP syntax check passes
3. Landing page accessible at URL
4. CSS/JS assets load (200 OK)
5. Mobile sticky CTA works
6. AI panel responds to input
7. FAQ accordion functions
8. Analytics tracking active
9. No JavaScript console errors
10. Performance: Page loads < 2s

---

## Support

- **Full Documentation**: `/LANDING-V2-PRO-MOBILE.md`
- **Quick Reference**: `/LANDING-V2-PRO-QUICKREF.md`
- **Main Deployment Guide**: `/DEPLOYMENT-poradnik-pro.md`

---

**Deployment Ready**: ✅ Yes
**Branch**: claude/create-landing-v2-pro
**Commits**: 2 (0a59ed6, 10e0d81)
**Files**: 13 (11 code + 2 docs)
**Size**: CSS 38KB, JS 12KB
