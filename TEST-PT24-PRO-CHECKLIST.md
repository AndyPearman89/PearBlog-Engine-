# ✅ PT24.PRO Production Testing Checklist

**Domain:** pt24.pro
**Test Date:** ___________
**Tester:** ___________
**Environment:** Production

---

## 🚀 Quick Start Testing Commands

### From Your Local Machine

```bash
# 1. Test homepage availability
curl -I https://pt24.pro
# Expected: HTTP/2 200

# 2. Test health endpoint
curl https://pt24.pro/wp-json/pearblog/v1/health
# Expected: {"status":"ok",...}

# 3. Test PT24 API
curl https://pt24.pro/wp-json/pt24/v1/businesses
# Expected: {"businesses":[...],...}

# 4. Test landing page
curl -I https://pt24.pro/mechanik/warszawa/
# Expected: HTTP/2 200

# 5. Test SSL certificate
echo | openssl s_client -servername pt24.pro -connect pt24.pro:443 2>/dev/null | grep "Verify return code"
# Expected: Verify return code: 0 (ok)
```

### Via SSH (Server-Side Testing)

```bash
# SSH to your server
ssh root@YOUR_SERVER_IP

# Navigate to WordPress directory
cd /var/www/pt24.pro

# Run PT24 platform statistics
wp pt24 stats --allow-root

# Expected output:
# PT24 Platform Statistics
# ========================
# Landing Pages: 100+
# Businesses: 5+
# Cities: 20+
# Service Categories: 5+
```

---

## 📋 Comprehensive Test Checklist

### 1. Basic Availability Tests

- [x] **Homepage loads**
  ```bash
  curl -I https://pt24.pro
  # Status: _____ (should be 200)
  ```

- [x] **WWW redirect works**
  ```bash
  curl -I https://www.pt24.pro
  # Status: _____ (should be 200 or 301)
  ```

- [x] **SSL certificate valid**
  ```bash
  echo | openssl s_client -servername pt24.pro -connect pt24.pro:443 2>/dev/null | grep "subject\|issuer\|notAfter"
  # Issuer: _______________
  # Expires: _______________
  ```

- [x] **DNS resolves correctly**
  ```bash
  dig pt24.pro +short
  # IP: _______________
  ```

### 2. PT24 Platform API Tests

- [x] **Health endpoint responds**
  ```bash
  curl https://pt24.pro/wp-json/pearblog/v1/health
  # Response: _______________
  ```

- [x] **Businesses API works**
  ```bash
  curl https://pt24.pro/wp-json/pt24/v1/businesses
  # Total businesses: _______________
  ```

- [x] **Businesses API with filters**
  ```bash
  curl "https://pt24.pro/wp-json/pt24/v1/businesses?service=mechanik&city=warszawa"
  # Filtered results: _______________
  ```

- [x] **Specific business endpoint**
  ```bash
  curl https://pt24.pro/wp-json/pt24/v1/businesses/1
  # Status: _______________
  ```

### 3. Landing Pages Tests

Test at least 3 different service/city combinations:

- [x] **Mechanik + Warszawa**
  ```bash
  curl -I https://pt24.pro/mechanik/warszawa/
  # Status: _______________
  ```

- [x] **Hydraulik + Kraków**
  ```bash
  curl -I https://pt24.pro/hydraulik/krakow/
  # Status: _______________
  ```

- [x] **Elektryk + Wrocław**
  ```bash
  curl -I https://pt24.pro/elektryk/wroclaw/
  # Status: _______________
  ```

- [x] **Laweta + Poznań**
  ```bash
  curl -I https://pt24.pro/laweta/poznan/
  # Status: _______________
  ```

- [x] **Wulkanizacja + Gdańsk**
  ```bash
  curl -I https://pt24.pro/wulkanizacja/gdansk/
  # Status: _______________
  ```

- [x] **404 for invalid service/city**
  ```bash
  curl -I https://pt24.pro/invalid-service/invalid-city/
  # Status: _____ (should be 404)
  ```

### 4. Lead Form Tests

- [x] **AJAX endpoint accessible**
  ```bash
  curl -X POST https://pt24.pro/wp-admin/admin-ajax.php \
    -d "action=pt24_submit_lead"
  # Response: _______________
  ```

- [x] **Manual form submission test**
  - Visit: https://pt24.pro/mechanik/warszawa/
  - Fill form with test data
  - Submit
  - Result: _______________

### 5. Frontend Tests (Manual - Use Browser)

Visit: **https://pt24.pro**

- [x] Homepage loads without errors
- [x] Service categories visible (mechanik, hydraulik, elektryk, laweta, wulkanizacja)
- [x] City list displayed
- [x] Search functionality works
- [x] Navigation menu present
- [x] Footer contains required information
- [x] Mobile responsive (test on mobile device or browser DevTools)
- [x] No JavaScript console errors

Visit: **https://pt24.pro/mechanik/warszawa/**

- [x] Landing page loads correctly
- [x] Service description visible
- [x] Lead form present and functional
- [x] Business listings shown (if any businesses exist)
- [x] CTA buttons work
- [x] Phone number click tracking active
- [x] Form validation works
- [x] Form submission succeeds
- [x] No JavaScript console errors

### 6. WP-CLI Tests (SSH Required)

SSH to server and run:

```bash
cd /var/www/pt24.pro

# Test 1: Platform statistics
wp pt24 stats --allow-root
```
- [x] Command executes successfully
- [x] Shows landing pages count: _____
- [x] Shows businesses count: _____
- [x] Shows cities count: _____
- [x] Shows service categories count: _____

```bash
# Test 2: List landing pages
wp post list --post_type=pt24_landing --posts_per_page=10 --allow-root
```
- [x] Command executes successfully
- [x] Landing pages listed: _____

```bash
# Test 3: List businesses
wp post list --post_type=pt24_business --posts_per_page=10 --allow-root
```
- [x] Command executes successfully
- [x] Businesses listed: _____

```bash
# Test 4: Check cities taxonomy
wp term list pt24_city --format=count --allow-root
```
- [x] Total cities: _____ (should be ≥20)

```bash
# Test 5: Check service categories
wp term list pt24_service_cat --format=count --allow-root
```
- [x] Total services: _____ (should be ≥5)

```bash
# Test 6: Check leads in database
wp db query "SELECT COUNT(*) as total FROM wp_pt24_leads" --allow-root
```
- [x] Total leads: _____

```bash
# Test 7: Check business stats table
wp db query "SELECT COUNT(*) as total FROM wp_pt24_business_stats" --allow-root
```
- [x] Total stats records: _____

```bash
# Test 8: Verify rewrite rules
wp rewrite list --format=count --allow-root
```
- [x] Rewrite rules exist: _____

### 7. Database Tests

```bash
# Check if PT24 tables exist
wp db tables --allow-root | grep pt24

# Expected tables:
# - wp_pt24_leads
# - wp_pt24_business_stats
# - wp_pt24_subscriptions (if applicable)
```

- [x] wp_pt24_leads exists
- [x] wp_pt24_business_stats exists
- [x] All required tables present

```bash
# Check recent leads
wp db query "SELECT * FROM wp_pt24_leads ORDER BY created_at DESC LIMIT 5" --allow-root
```
- [x] Query executes: _____
- [x] Results shown: _____

### 8. Performance Tests

- [x] **Homepage load time**
  ```bash
  time curl -o /dev/null -s https://pt24.pro
  # Time: _____ seconds (should be < 2s)
  ```

- [x] **Landing page load time**
  ```bash
  time curl -o /dev/null -s https://pt24.pro/mechanik/warszawa/
  # Time: _____ seconds (should be < 1.5s)
  ```

- [x] **API response time**
  ```bash
  time curl -o /dev/null -s https://pt24.pro/wp-json/pt24/v1/businesses
  # Time: _____ seconds (should be < 0.5s)
  ```

- [x] **TTFB (Time To First Byte)**
  ```bash
  curl -o /dev/null -s -w "TTFB: %{time_starttransfer}s\n" https://pt24.pro
  # TTFB: _____ seconds (should be < 0.5s)
  ```

### 9. Security Tests

- [x] **wp-config.php protected**
  ```bash
  curl -I https://pt24.pro/wp-config.php
  # Status: _____ (should be 403 or 404)
  ```

- [x] **readme.html removed**
  ```bash
  curl -I https://pt24.pro/readme.html
  # Status: _____ (should be 403 or 404)
  ```

- [x] **XML-RPC disabled**
  ```bash
  curl -X POST https://pt24.pro/xmlrpc.php
  # Response: _______________
  ```

- [x] **Directory listing disabled**
  ```bash
  curl -I https://pt24.pro/wp-content/uploads/
  # Status: _____ (should be 403)
  ```

- [x] **Security headers present**
  ```bash
  curl -I https://pt24.pro | grep -E "X-Frame-Options|X-Content-Type-Options|Strict-Transport-Security"
  # Headers found: _______________
  ```

### 10. Admin Panel Tests (Manual)

Visit: **https://pt24.pro/wp-admin**

- [x] Login page loads
- [x] Can login with credentials
- [x] Dashboard loads correctly
- [x] PT24 Landing Pages menu exists
- [x] PT24 Businesses menu exists
- [x] Can view list of landing pages
- [x] Can view list of businesses
- [x] Can add new business
- [x] Can edit existing business
- [x] Can view leads (if admin page exists)
- [x] No PHP errors in debug.log

### 11. Server Health Tests

```bash
# Check disk space
df -h
```
- [x] Free space available: _____ (should be >20%)

```bash
# Check memory usage
free -h
```
- [x] Free memory: _____ (should be >500MB)

```bash
# Check PHP-FPM status
systemctl status php8.1-fpm
```
- [x] PHP-FPM: _____ (should be active/running)

```bash
# Check MySQL status
systemctl status mysql
```
- [x] MySQL: _____ (should be active/running)

```bash
# Check web server status
systemctl status apache2  # or nginx
```
- [x] Web server: _____ (should be active/running)

### 12. Log File Tests

```bash
# Check WordPress error log
tail -n 50 /var/www/pt24.pro/wp-content/debug.log
```
- [x] No critical errors
- [x] Notes: _______________

```bash
# Check web server error log
tail -n 50 /var/log/apache2/pt24-error.log  # or nginx
```
- [x] No critical errors
- [x] Notes: _______________

```bash
# Check for PHP errors
grep -i "fatal\|error" /var/log/apache2/pt24-error.log | tail -n 10
```
- [x] No fatal errors
- [x] Notes: _______________

---

## ✅ Success Criteria

PT24.PRO is considered **HEALTHY** if:

- ✅ Homepage loads (HTTP 200)
- ✅ SSL certificate valid and not expiring soon
- ✅ Health API returns OK
- ✅ PT24 API endpoints respond correctly
- ✅ At least 10+ landing pages exist
- ✅ Landing page URLs work (/{service}/{city}/)
- ✅ Lead form submissions work
- ✅ Database tables exist and contain data
- ✅ Platform statistics accessible via WP-CLI
- ✅ No critical errors in logs
- ✅ Performance metrics within targets
- ✅ Security checks pass

---

## 🚨 Common Issues & Quick Fixes

### Issue: Landing pages return 404

**Fix:**
```bash
wp rewrite flush --allow-root
wp pt24 init --allow-root
```

### Issue: No landing pages exist

**Fix:**
```bash
wp pt24 init --allow-root
wp pt24 generate-pages --batch=10 --allow-root
```

### Issue: PT24 API returns empty results

**Fix:**
```bash
# Check if businesses exist
wp post list --post_type=pt24_business --allow-root

# If none, add test business
# (See DEPLOYMENT-pt24-pro.md for business creation commands)
```

### Issue: Database tables missing

**Fix:**
```bash
wp pt24 init --allow-root
```

### Issue: Form submissions not working

**Fix:**
```bash
# Check if MU plugin is loaded
ls -la /var/www/pt24.pro/wp-content/mu-plugins/ | grep pt24

# Check AJAX handler
grep "pt24_submit_lead" /var/www/pt24.pro/wp-content/themes/pearblog-theme/functions.php
```

---

## 📊 Test Results Summary

**Overall Status:** [ ] PASS  [ ] FAIL  [ ] ISSUES

**Tests Executed:** _____ / 50+

**Tests Passed:** _____

**Tests Failed:** _____

**Critical Issues Found:**
1. _______________
2. _______________
3. _______________

**Non-Critical Issues:**
1. _______________
2. _______________
3. _______________

**Performance Notes:**
- Homepage load time: _____
- API response time: _____
- Database query time: _____

**Recommendations:**
1. _______________
2. _______________
3. _______________

---

## 📝 Notes

**Additional Observations:**

_______________________________________________
_______________________________________________
_______________________________________________
_______________________________________________

**Follow-up Required:**

_______________________________________________
_______________________________________________
_______________________________________________

---

## 📞 Support & Documentation

- **Deployment Guide:** [DEPLOYMENT-pt24-pro.md](DEPLOYMENT-pt24-pro.md)
- **Quick Start:** [QUICKSTART-pt24-pro.md](QUICKSTART-pt24-pro.md)
- **Full Testing Guide:** [TEST-pt24-pro-poradnik-pro.md](TEST-pt24-pro-poradnik-pro.md)
- **Troubleshooting:** [TROUBLESHOOTING.md](TROUBLESHOOTING.md)
- **GitHub Issues:** https://github.com/AndyPearman89/PearBlog-Engine-/issues

---

**Test Completed By:** _______________
**Date:** _______________
**Signature:** _______________

---

**Checklist Version:** 1.0
**Last Updated:** 2026-05-04
**Status:** Ready for use ✅
