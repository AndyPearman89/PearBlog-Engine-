# 🧪 Test Plan: pt24.pro i poradnik.pro

**Date:** 2026-05-04
**Domains:** pt24.pro, poradnik.pro
**Purpose:** Comprehensive verification of both production deployments

---

## 📋 Overview

This document provides comprehensive testing procedures for:
- **pt24.pro** - Local services directory platform (PT24 Platform v2.0)
- **poradnik.pro** - Practical guides/advice blog (PearBlog Engine v6.0)

---

## 🎯 pt24.pro Testing

### 1. Basic Availability Tests

```bash
# Test homepage
curl -I https://pt24.pro
# Expected: HTTP/2 200

# Test WWW redirect
curl -I https://www.pt24.pro
# Expected: HTTP/2 301 or 200

# Test SSL certificate
openssl s_client -connect pt24.pro:443 -servername pt24.pro < /dev/null 2>/dev/null | grep "Verify return code"
# Expected: Verify return code: 0 (ok)

# Test health endpoint
curl https://pt24.pro/wp-json/pearblog/v1/health
# Expected: {"status":"ok",...}
```

### 2. PT24 Platform API Tests

```bash
# Test businesses API endpoint
curl https://pt24.pro/wp-json/pt24/v1/businesses
# Expected: {"businesses":[...],"total":...}

# Test businesses with filters
curl "https://pt24.pro/wp-json/pt24/v1/businesses?service=mechanik&city=warszawa"
# Expected: Filtered business list

# Test specific business
curl https://pt24.pro/wp-json/pt24/v1/businesses/1
# Expected: Single business details or 404

# Test business stats endpoint
curl https://pt24.pro/wp-json/pt24/v1/stats/1
# Expected: Stats object or error message
```

### 3. Landing Pages Tests

```bash
# Test landing page URL structure
curl -I https://pt24.pro/mechanik/warszawa/
# Expected: HTTP/2 200

curl -I https://pt24.pro/hydraulik/krakow/
# Expected: HTTP/2 200

curl -I https://pt24.pro/elektryk/wroclaw/
# Expected: HTTP/2 200

# Test invalid service/city combination
curl -I https://pt24.pro/invalid-service/invalid-city/
# Expected: HTTP/2 404
```

### 4. Lead Form Tests

```bash
# Test lead submission endpoint
curl -X POST https://pt24.pro/wp-admin/admin-ajax.php \
  -d "action=pt24_submit_lead" \
  -d "name=Test User" \
  -d "email=test@example.com" \
  -d "phone=+48123456789" \
  -d "city=Warszawa" \
  -d "service=mechanik" \
  -d "message=Test message" \
  -d "consent=1"
# Expected: Success response or validation errors
```

### 5. PT24 WP-CLI Tests (SSH Required)

```bash
# SSH to server
ssh root@YOUR_SERVER_IP

cd /var/www/pt24.pro

# Test PT24 platform statistics
wp pt24 stats --allow-root
# Expected: Statistics showing landing pages, businesses, cities, services

# List landing pages
wp post list --post_type=pt24_landing --posts_per_page=5 --allow-root
# Expected: List of landing pages

# List businesses
wp post list --post_type=pt24_business --posts_per_page=5 --allow-root
# Expected: List of businesses

# Check cities
wp term list pt24_city --format=count --allow-root
# Expected: Number >= 20

# Check service categories
wp term list pt24_service_cat --format=count --allow-root
# Expected: Number >= 5

# Check leads in database
wp db query "SELECT COUNT(*) as total FROM wp_pt24_leads" --allow-root
# Expected: Total lead count

# Check business stats table
wp db query "SELECT COUNT(*) as total FROM wp_pt24_business_stats" --allow-root
# Expected: Stats count
```

### 6. PT24 Frontend Tests (Manual)

**Visit:** https://pt24.pro

- [x] Homepage loads correctly
- [x] Service categories are visible
- [x] City list is displayed
- [x] Search functionality works
- [x] Navigation menu is present
- [x] Footer contains required information
- [x] Mobile responsive design works

**Visit:** https://pt24.pro/mechanik/warszawa/

- [x] Landing page loads correctly
- [x] Service description is displayed
- [x] Lead form is visible
- [x] Business listings are shown (if any)
- [x] CTA buttons work
- [x] Phone number click tracking works
- [x] Form submission works

**Admin Panel:** https://pt24.pro/wp-admin

- [x] Login works
- [x] PT24 Landing Pages menu exists
- [x] PT24 Businesses menu exists
- [x] Can add new business
- [x] Can edit business
- [x] Can view leads (if custom admin page exists)

---

## 🎯 poradnik.pro Testing

### 1. Basic Availability Tests

```bash
# Test homepage
curl -I https://poradnik.pro
# Expected: HTTP/2 200

# Test WWW redirect
curl -I https://www.poradnik.pro
# Expected: HTTP/2 301 or 200

# Test SSL certificate
openssl s_client -connect poradnik.pro:443 -servername poradnik.pro < /dev/null 2>/dev/null | grep "Verify return code"
# Expected: Verify return code: 0 (ok)

# Test health endpoint
curl https://poradnik.pro/wp-json/pearblog/v1/health
# Expected: {"status":"ok","timestamp":...}
```

### 2. PearBlog API Tests

```bash
# Test PearBlog health endpoint
curl https://poradnik.pro/wp-json/pearblog/v1/health
# Expected: {"status":"ok",...}

# Test posts endpoint
curl https://poradnik.pro/wp-json/wp/v2/posts?per_page=5
# Expected: Array of posts

# Test categories
curl https://poradnik.pro/wp-json/wp/v2/categories
# Expected: Array of categories

# Test tags
curl https://poradnik.pro/wp-json/wp/v2/tags
# Expected: Array of tags
```

### 3. Content Generation Tests (SSH Required)

```bash
# SSH to server
ssh root@204.48.27.118

cd /var/www/poradnik.pro

# Check PearBlog Engine status
wp pearblog stats --allow-root
# Expected: Statistics showing generated posts, cost, etc.

# Check if articles exist
wp post list --post_type=post --posts_per_page=10 --allow-root
# Expected: List of published articles

# Check queue
wp pearblog queue list --allow-root
# Expected: List of topics in queue

# Check OpenAI API key is configured
wp option get pearblog_openai_api_key --allow-root
# Expected: API key (sk-proj-...)

# Test manual generation (optional - costs money)
# wp pearblog generate --allow-root
# Expected: New article generated

# Check autonomous mode
wp option get pearblog_autonomous_mode --allow-root
# Expected: 1 (enabled)

# Check autopilot status
wp pearblog autopilot status --allow-root
# Expected: Status information
```

### 4. WP-Cron Tests

```bash
# List scheduled cron events
wp cron event list --allow-root | grep pearblog
# Expected: pearblog_content_pipeline scheduled hourly

# Run cron manually (for testing)
wp cron event run pearblog_content_pipeline --allow-root
# Expected: Cron event executed

# Check if cron is disabled
wp option get disable_wp_cron --allow-root
# Expected: Empty or 0 (cron should be enabled)
```

### 5. Log File Tests

```bash
# Check if log files exist
ls -lh /var/www/poradnik.pro/wp-content/*.log
# Expected: debug.log, pearblog-engine.log

# Check recent log entries
tail -n 50 /var/www/poradnik.pro/wp-content/pearblog-engine.log
# Expected: Recent log entries (no critical errors)

# Check for errors
grep ERROR /var/www/poradnik.pro/wp-content/pearblog-engine.log | tail -n 10
# Expected: No critical errors (or manageable errors)

# Check WordPress debug log
tail -n 50 /var/www/poradnik.pro/wp-content/debug.log
# Expected: Recent entries (should be minimal in production)
```

### 6. Database Tests

```bash
# Check WordPress database tables
wp db tables --allow-root
# Expected: List of all WP tables with prd_ prefix

# Check posts count
wp db query "SELECT COUNT(*) as total FROM prd_posts WHERE post_type='post' AND post_status='publish'" --allow-root
# Expected: Number of published posts

# Check options
wp option get pearblog_industry --allow-root
# Expected: "poradniki" or similar

wp option get pearblog_language --allow-root
# Expected: "pl"

wp option get pearblog_publish_rate --allow-root
# Expected: "1" or configured rate

# Check AI cost tracking
wp option get pearblog_ai_cost_cents --allow-root
# Expected: Number (total cost in cents)
```

### 7. Performance Tests

```bash
# Test page load time
time curl -o /dev/null -s -w "Total: %{time_total}s\n" https://poradnik.pro
# Expected: < 2 seconds

# Test TTFB (Time To First Byte)
curl -o /dev/null -s -w "TTFB: %{time_starttransfer}s\n" https://poradnik.pro
# Expected: < 0.5 seconds

# Check PHP-FPM status (if enabled)
systemctl status php8.1-fpm
# Expected: active (running)

# Check MySQL status
systemctl status mysql
# Expected: active (running)

# Check web server status
systemctl status apache2  # or nginx
# Expected: active (running)
```

### 8. Frontend Tests (Manual)

**Visit:** https://poradnik.pro

- [x] Homepage loads correctly
- [x] Latest articles are displayed
- [x] Navigation menu works
- [x] Categories are visible
- [x] Search functionality works
- [x] Footer contains required information
- [x] Mobile responsive design works
- [x] Images load correctly
- [x] Social share buttons work (if present)

**Visit any article:**

- [x] Article content displays correctly
- [x] Images are present and load
- [x] Headings are formatted properly
- [x] Internal links work
- [x] Table of contents works (if present)
- [x] Comments section visible (if enabled)
- [x] Related articles shown (if configured)

**Admin Panel:** https://poradnik.pro/wp-admin

- [x] Login works
- [x] Dashboard loads
- [x] PearBlog Engine menu exists
- [x] Posts list shows generated articles
- [x] Can manually create/edit posts
- [x] PearBlog settings accessible
- [x] Queue management works
- [x] Stats dashboard shows data

---

## 🔐 Security Tests

### For both pt24.pro and poradnik.pro:

```bash
# Test if wp-config.php is accessible (should NOT be)
curl -I https://pt24.pro/wp-config.php
curl -I https://poradnik.pro/wp-config.php
# Expected: HTTP/2 403 (Forbidden) or 404

# Test if readme.html is removed/protected
curl -I https://pt24.pro/readme.html
curl -I https://poradnik.pro/readme.html
# Expected: HTTP/2 403 or 404

# Test XML-RPC (should be disabled)
curl -X POST https://pt24.pro/xmlrpc.php
curl -X POST https://poradnik.pro/xmlrpc.php
# Expected: Empty response or XML-RPC disabled message

# Check for directory listing
curl -I https://pt24.pro/wp-content/uploads/
curl -I https://poradnik.pro/wp-content/uploads/
# Expected: HTTP/2 403 (directory listing disabled)

# Test security headers
curl -I https://pt24.pro | grep -E "X-Frame-Options|X-Content-Type-Options|Strict-Transport-Security"
curl -I https://poradnik.pro | grep -E "X-Frame-Options|X-Content-Type-Options|Strict-Transport-Security"
# Expected: Security headers present
```

---

## 📊 Performance Benchmarks

### Expected Performance Metrics:

**pt24.pro:**
- Homepage load time: < 2s
- Landing page load time: < 1.5s
- API response time: < 500ms
- TTFB: < 500ms

**poradnik.pro:**
- Homepage load time: < 2s
- Article page load time: < 2s
- API response time: < 500ms
- TTFB: < 500ms

### Load Testing (Optional):

```bash
# Install Apache Bench (if not present)
apt install -y apache2-utils

# Test pt24.pro
ab -n 100 -c 10 https://pt24.pro/
# Expected: 100% successful requests, no failures

# Test poradnik.pro
ab -n 100 -c 10 https://poradnik.pro/
# Expected: 100% successful requests, no failures
```

---

## ✅ Success Criteria

### pt24.pro is considered healthy if:

- [x] Homepage loads (HTTP 200)
- [x] SSL certificate valid
- [x] Health API returns OK
- [x] PT24 API endpoints respond
- [x] At least 10+ landing pages exist
- [x] Landing page URLs work (/{service}/{city}/)
- [x] Lead form submissions work
- [x] Database tables exist (wp_pt24_leads, wp_pt24_business_stats)
- [x] No critical errors in logs
- [x] Platform statistics accessible via WP-CLI

### poradnik.pro is considered healthy if:

- [x] Homepage loads (HTTP 200)
- [x] SSL certificate valid
- [x] Health API returns OK
- [x] At least 1+ published article exists
- [x] Content pipeline scheduled (hourly)
- [x] Autonomous mode enabled
- [x] Autopilot running
- [x] OpenAI API key configured
- [x] No critical errors in logs
- [x] Posts generated automatically

---

## 🚨 Critical Issues to Check

### Both Platforms:

1. **SSL Certificate Expiration**
   ```bash
   echo | openssl s_client -servername pt24.pro -connect pt24.pro:443 2>/dev/null | openssl x509 -noout -dates
   echo | openssl s_client -servername poradnik.pro -connect poradnik.pro:443 2>/dev/null | openssl x509 -noout -dates
   # Check: Not expired, auto-renewal configured
   ```

2. **Disk Space**
   ```bash
   df -h
   # Check: At least 20% free space
   ```

3. **Memory Usage**
   ```bash
   free -h
   # Check: At least 500MB free
   ```

4. **Database Size**
   ```bash
   wp db size --allow-root
   # Check: Within reasonable limits
   ```

---

## 📝 Test Execution Log Template

```
Test Date: YYYY-MM-DD HH:MM
Tester: [Name]
Environment: Production

=== pt24.pro Tests ===
[ ] Basic availability
[ ] SSL certificate
[ ] Health endpoint
[ ] PT24 API endpoints
[ ] Landing pages
[ ] Lead form
[ ] WP-CLI commands
[ ] Database integrity
[ ] Frontend functionality

Issues found: [List or "None"]

=== poradnik.pro Tests ===
[ ] Basic availability
[ ] SSL certificate
[ ] Health endpoint
[ ] PearBlog API
[ ] Content generation
[ ] WP-Cron
[ ] Log files
[ ] Database
[ ] Performance
[ ] Frontend functionality

Issues found: [List or "None"]

=== Security Tests ===
[ ] wp-config.php protected
[ ] XML-RPC disabled
[ ] Directory listing disabled
[ ] Security headers present

Issues found: [List or "None"]

Overall Status: [PASS / FAIL / ISSUES]

Notes:
[Additional notes]
```

---

## 🔧 Quick Fix Commands

### If pt24.pro has issues:

```bash
# Flush rewrite rules
wp rewrite flush --allow-root

# Re-initialize PT24 platform
wp pt24 init --allow-root

# Regenerate landing pages
wp pt24 generate-pages --batch=10 --allow-root

# Check and repair database
wp db repair --allow-root
wp db optimize --allow-root

# Clear cache (if caching plugin installed)
wp cache flush --allow-root
```

### If poradnik.pro has issues:

```bash
# Reset circuit breaker
wp pearblog circuit reset --allow-root

# Manually run content pipeline
wp cron event run pearblog_content_pipeline --allow-root

# Check API key
wp option get pearblog_openai_api_key --allow-root

# Restart autopilot
wp pearblog autopilot start --allow-root

# Check and repair database
wp db repair --allow-root
wp db optimize --allow-root

# Clear cache
wp cache flush --allow-root
```

---

## 📞 Support

**Documentation:**
- pt24.pro: [DEPLOYMENT-pt24-pro.md](DEPLOYMENT-pt24-pro.md)
- poradnik.pro: [DEPLOYMENT-poradnik-pro.md](DEPLOYMENT-poradnik-pro.md)
- Troubleshooting: [TROUBLESHOOTING.md](TROUBLESHOOTING.md)

**GitHub:**
- Issues: https://github.com/AndyPearman89/PearBlog-Engine-/issues
- Discussions: https://github.com/AndyPearman89/PearBlog-Engine-/discussions

---

**Test Plan Version:** 1.0
**Last Updated:** 2026-05-04
**Status:** Ready for use 🧪
